<?php
// backend/controllers/admin/ReportsController.php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/AdminController.php';

// ponytail: PDF & XLSX renderer dibuat mandiri menggunakan ZipArchive & spec PDF 1.4 native tanpa vendor composer.
class LaporanController {
    /**
     * Mengambil data laporan terpadu berdasarkan rentang tanggal
     */
    public static function fetchReportData(string $startDate, string $endDate, bool $forExport = false): array {
        $db = getDB();

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate)) {
            $startDate = date('Y-m-01');
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDate)) {
            $endDate = date('Y-m-d');
        }
        if ($startDate > $endDate) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }

        // 1. Ringkasan Eksekutif dalam periode
        $txStmt = $db->prepare("
            SELECT 
                COUNT(*) as count,
                COALESCE(SUM(jumlah_bayar), 0) as total
            FROM transaksi
            WHERE DATE(dibayar_pada) BETWEEN ? AND ?
        ");
        $txStmt->execute([$startDate, $endDate]);
        $txSummary = $txStmt->fetch();
        $periodRevenue = (float)($txSummary['total'] ?? 0);
        $periodTxCount = (int)($txSummary['count'] ?? 0);

        // Total presensi dalam periode
        $attStmt = $db->prepare("
            SELECT COUNT(*) 
            FROM presensi 
            WHERE DATE(waktu_presensi) BETWEEN ? AND ?
        ");
        $attStmt->execute([$startDate, $endDate]);
        $periodCheckins = (int)$attStmt->fetchColumn();

        // Member baru terdaftar dalam periode
        $userStmt = $db->prepare("
            SELECT COUNT(*) 
            FROM users 
            WHERE role = 'user' 
              AND DATE(created_at) BETWEEN ? AND ?
        ");
        $userStmt->execute([$startDate, $endDate]);
        $newMembers = (int)$userStmt->fetchColumn();

        // 2. Transaksi terperinci dalam periode
        $limit = $forExport ? 5000 : 200;
        $detailStmt = $db->prepare("
            SELECT 
                t.id,
                t.jumlah_bayar,
                t.metode_pembayaran,
                t.dibayar_pada,
                u.id as user_id,
                u.name as member_name,
                u.member_code,
                u.phone as member_phone,
                p.nama_paket,
                p.durasi_hari
            FROM transaksi t
            JOIN langganan l ON t.id_langganan = l.id
            JOIN users u ON l.id_user = u.id
            JOIN paket p ON l.id_paket = p.id
            WHERE DATE(t.dibayar_pada) BETWEEN ? AND ?
            ORDER BY t.dibayar_pada DESC, t.id DESC
            LIMIT {$limit}
        ");
        $detailStmt->execute([$startDate, $endDate]);
        $txRows = $detailStmt->fetchAll();

        $transactions = [];
        foreach ($txRows as $row) {
            $amount = (float)$row['jumlah_bayar'];
            $invoiceCode = 'TRX-' . date('Ymd', strtotime($row['dibayar_pada'])) . '-' . str_pad((string)$row['id'], 4, '0', STR_PAD_LEFT);
            $transactions[] = [
                'id'                  => (int)$row['id'],
                'invoice_code'        => $invoiceCode,
                'member_name'         => $row['member_name'],
                'member_code'         => $row['member_code'],
                'member_phone'        => $row['member_phone'] ?? '-',
                'nama_paket'          => $row['nama_paket'],
                'durasi_hari'         => (int)$row['durasi_hari'],
                'jumlah_bayar'        => $amount,
                'jumlah_bayar_format' => 'Rp ' . number_format($amount, 0, ',', '.'),
                'metode_pembayaran'   => strtoupper($row['metode_pembayaran']),
                'dibayar_pada'        => $row['dibayar_pada'],
                'waktu_format'        => date('d M Y H:i', strtotime($row['dibayar_pada'])),
            ];
        }

        // 3. Distribusi metode pembayaran dalam periode
        $methStmt = $db->prepare("
            SELECT 
                metode_pembayaran, 
                COUNT(*) as transaksi_count, 
                COALESCE(SUM(jumlah_bayar), 0) as total_nominal 
            FROM transaksi 
            WHERE DATE(dibayar_pada) BETWEEN ? AND ?
            GROUP BY metode_pembayaran
        ");
        $methStmt->execute([$startDate, $endDate]);
        $methodStats = $methStmt->fetchAll();

        $cashTotal = 0.0;
        $nonCashTotal = 0.0;
        foreach ($methodStats as $ms) {
            $mName = strtolower($ms['metode_pembayaran'] ?? '');
            $mNominal = (float)($ms['total_nominal'] ?? 0);
            if ($mName === 'tunai') {
                $cashTotal += $mNominal;
            } else {
                $nonCashTotal += $mNominal;
            }
        }
        $avgTx = $periodTxCount > 0 ? (float)round($periodRevenue / $periodTxCount) : 0.0;

        // 4. Performa paket dalam periode
        $pkgStmt = $db->prepare("
            SELECT 
                p.id,
                p.nama_paket,
                p.durasi_hari,
                CAST(p.harga AS DOUBLE) as harga,
                COUNT(t.id) as total_terjual,
                COALESCE(SUM(t.jumlah_bayar), 0) as total_pendapatan
            FROM paket p
            LEFT JOIN langganan l ON l.id_paket = p.id AND DATE(l.tanggal_mulai) BETWEEN ? AND ?
            LEFT JOIN transaksi t ON t.id_langganan = l.id
            GROUP BY p.id
            ORDER BY total_pendapatan DESC, total_terjual DESC
        ");
        $pkgStmt->execute([$startDate, $endDate]);
        $packageStats = $pkgStmt->fetchAll();

        foreach ($packageStats as &$pkg) {
            $pkg['id'] = (int)$pkg['id'];
            $pkg['durasi_hari'] = (int)$pkg['durasi_hari'];
            $pkg['harga'] = (float)$pkg['harga'];
            $pkg['harga_format'] = 'Rp ' . number_format($pkg['harga'], 0, ',', '.');
            $pkg['total_terjual'] = (int)$pkg['total_terjual'];
            $pkg['total_pendapatan'] = (float)$pkg['total_pendapatan'];
            $pkg['total_pendapatan_format'] = 'Rp ' . number_format($pkg['total_pendapatan'], 0, ',', '.');
        }

        // 5. Riwayat presensi dalam periode
        $attLimit = $forExport ? 2000 : 150;
        $attLogStmt = $db->prepare("
            SELECT 
                pr.id,
                pr.waktu_presensi,
                u.name as member_name,
                u.member_code,
                u.gender
            FROM presensi pr
            JOIN users u ON pr.id_user = u.id
            WHERE DATE(pr.waktu_presensi) BETWEEN ? AND ?
            ORDER BY pr.waktu_presensi DESC
            LIMIT {$attLimit}
        ");
        $attLogStmt->execute([$startDate, $endDate]);
        $attRows = $attLogStmt->fetchAll();

        $attendances = [];
        foreach ($attRows as $ar) {
            $wTime = strtotime($ar['waktu_presensi']);
            $attendances[] = [
                'id'             => (int)$ar['id'],
                'member_name'    => $ar['member_name'],
                'member_code'    => $ar['member_code'],
                'gender'         => strtolower($ar['gender'] ?? 'pria'),
                'gender_label'   => strtolower($ar['gender'] ?? '') === 'wanita' ? 'Wanita' : 'Pria',
                'waktu_presensi' => $ar['waktu_presensi'],
                'tanggal'        => date('d M Y', $wTime),
                'jam'            => date('H:i', $wTime) . ' WIB',
            ];
        }

        // 6. Angka kumulatif keseluruhan untuk kompatibilitas
        $allRevenue = (float)$db->query("SELECT COALESCE(SUM(jumlah_bayar), 0) FROM transaksi")->fetchColumn();
        $curMonthRev = (float)$db->query("
            SELECT COALESCE(SUM(jumlah_bayar), 0) 
            FROM transaksi 
            WHERE MONTH(dibayar_pada) = MONTH(CURDATE()) 
              AND YEAR(dibayar_pada) = YEAR(CURDATE())
        ")->fetchColumn();

        return [
            'period' => [
                'start_date' => $startDate,
                'end_date'   => $endDate,
            ],
            'summary' => [
                'revenue'                       => $periodRevenue,
                'revenue_formatted'             => 'Rp ' . number_format($periodRevenue, 0, ',', '.'),
                'transaction_count'             => $periodTxCount,
                'average_transaction'           => $avgTx,
                'average_transaction_formatted' => 'Rp ' . number_format($avgTx, 0, ',', '.'),
                'cash_total'                    => $cashTotal,
                'cash_total_formatted'          => 'Rp ' . number_format($cashTotal, 0, ',', '.'),
                'non_cash_total'                => $nonCashTotal,
                'non_cash_total_formatted'      => 'Rp ' . number_format($nonCashTotal, 0, ',', '.'),
                'checkin_count'                 => $periodCheckins,
                'new_members'                   => $newMembers,
            ],
            'transactions'            => $transactions,
            'method_stats'            => $methodStats,
            'packages'                => $packageStats,
            'attendances'             => $attendances,
            'total_revenue'           => $allRevenue,
            'total_revenue_formatted' => 'Rp ' . number_format($allRevenue, 0, ',', '.'),
            'month_revenue'           => $curMonthRev,
            'month_revenue_formatted' => 'Rp ' . number_format($curMonthRev, 0, ',', '.'),
        ];
    }

    /**
     * Endpoint JSON data laporan
     * GET /api/admin/reports
     */
    public static function index(): void {
        AdminController::requireAdmin();

        $startDate = trim($_GET['start_date'] ?? date('Y-m-01'));
        $endDate = trim($_GET['end_date'] ?? date('Y-m-d'));

        $data = self::fetchReportData($startDate, $endDate, false);

        AdminController::json([
            'status' => 'success',
            'data'   => $data,
        ]);
    }

    /**
     * Export data laporan ke format Excel terbaru (.xlsx)
     * GET /api/admin/reports?export=xlsx&start_date=...&end_date=...
     */
    public static function exportXlsx(): void {
        AdminController::requireAdmin();

        $startDate = trim($_GET['start_date'] ?? date('Y-m-01'));
        $endDate = trim($_GET['end_date'] ?? date('Y-m-d'));

        $data = self::fetchReportData($startDate, $endDate, true);
        $binary = self::generateXlsxBinary($data);

        $filename = "Laporan_KelolaGym_{$startDate}_sd_{$endDate}.xlsx";

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($binary));
        header('Cache-Control: max-age=0');
        header('Pragma: public');

        echo $binary;
    }

    /**
     * Export data laporan ke format PDF
     * GET /api/admin/reports?export=pdf&start_date=...&end_date=...
     */
    public static function exportPdf(): void {
        AdminController::requireAdmin();

        $startDate = trim($_GET['start_date'] ?? date('Y-m-01'));
        $endDate = trim($_GET['end_date'] ?? date('Y-m-d'));

        $data = self::fetchReportData($startDate, $endDate, true);
        $binary = self::generatePdfBinary($data);

        $filename = "Laporan_KelolaGym_{$startDate}_sd_{$endDate}.pdf";

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($binary));
        header('Cache-Control: max-age=0');
        header('Pragma: public');

        echo $binary;
    }

    /**
     * Generator dokumen Excel OpenXML (.xlsx) standar terbaru
     */
    private static function generateXlsxBinary(array $data): string {
        $tempFile = tempnam(sys_get_temp_dir(), 'gym_xlsx_');
        $zip = new ZipArchive();
        if ($zip->open($tempFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Gagal membuat file arsip Excel');
        }

        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
  <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
  <Default Extension="xml" ContentType="application/xml"/>
  <Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
  <Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
  <Override PartName="/xl/worksheets/sheet2.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
  <Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>
</Types>');

        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
</Relationships>');

        $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>
  <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet2.xml"/>
  <Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>
</Relationships>');

        $zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
  <sheets>
    <sheet name="Keuangan &amp; Transaksi" sheetId="1" r:id="rId1"/>
    <sheet name="Presensi Member" sheetId="2" r:id="rId2"/>
  </sheets>
</workbook>');

        // Definisi styles.xml:
        // 0: Normal
        // 1: Header table (Orange fill, white text bold, border)
        // 2: Title bold 14pt
        // 3: Subtitle / Section title gray fill bold 11pt
        // 4: Table cell with thin border
        // 5: Currency cell (Rp #,##0) with thin border
        // 6: Integer cell (#,##0) with thin border
        $zip->addFromString('xl/styles.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
  <numFmts count="2">
    <numFmt numFmtId="164" formatCode="&quot;Rp &quot;#,##0"/>
    <numFmt numFmtId="165" formatCode="#,##0"/>
  </numFmts>
  <fonts count="4">
    <font><name val="Calibri"/><sz val="11"/></font>
    <font><b/><name val="Calibri"/><sz val="11"/><color rgb="FFFFFFFF"/></font>
    <font><b/><name val="Calibri"/><sz val="14"/><color rgb="FF111827"/></font>
    <font><b/><name val="Calibri"/><sz val="11"/><color rgb="FF1F2937"/></font>
  </fonts>
  <fills count="4">
    <fill><patternFill patternType="none"/></fill>
    <fill><patternFill patternType="gray125"/></fill>
    <fill><patternFill patternType="solid"><fgColor rgb="FFFF7A1A"/></patternFill></fill>
    <fill><patternFill patternType="solid"><fgColor rgb="FFF3F4F6"/></patternFill></fill>
  </fills>
  <borders count="2">
    <border><left/><right/><top/><bottom/><diagonal/></border>
    <border>
      <left style="thin"><color rgb="FFE5E7EB"/></left>
      <right style="thin"><color rgb="FFE5E7EB"/></right>
      <top style="thin"><color rgb="FFE5E7EB"/></top>
      <bottom style="thin"><color rgb="FFE5E7EB"/></bottom>
    </border>
  </borders>
  <cellStyleXfs count="1">
    <xf numFmtId="0" fontId="0" fillId="0" borderId="0"/>
  </cellStyleXfs>
  <cellXfs count="7">
    <xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>
    <xf numFmtId="0" fontId="1" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1"/>
    <xf numFmtId="0" fontId="2" fillId="0" borderId="0" xfId="0" applyFont="1"/>
    <xf numFmtId="0" fontId="3" fillId="3" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1"/>
    <xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1"/>
    <xf numFmtId="164" fontId="0" fillId="0" borderId="1" xfId="0" applyNumberFormat="1" applyBorder="1"/>
    <xf numFmtId="165" fontId="0" fillId="0" borderId="1" xfId="0" applyNumberFormat="1" applyBorder="1"/>
  </cellXfs>
</styleSheet>');

        $colLetter = function(int $c): string {
            $l = '';
            while ($c > 0) {
                $m = ($c - 1) % 26;
                $l = chr(65 + $m) . $l;
                $c = (int)(($c - $m) / 26);
            }
            return $l;
        };

        // Sheet 1: Keuangan & Transaksi
        $rowIdx1 = 1;
        $s1Rows = [];
        $addRow1 = function(array $cells) use (&$rowIdx1, &$s1Rows, $colLetter): void {
            $xml = "<row r=\"{$rowIdx1}\">";
            foreach ($cells as $cIdx => $cell) {
                $pos = $colLetter($cIdx + 1) . $rowIdx1;
                $style = isset($cell['s']) ? " s=\"{$cell['s']}\"" : '';
                $type = $cell['t'] ?? 'str';
                $val = $cell['v'] ?? '';
                if ($type === 'num') {
                    $xml .= "<c r=\"{$pos}\"{$style}><v>{$val}</v></c>";
                } else {
                    $esc = htmlspecialchars((string)$val, ENT_QUOTES | ENT_XML1, 'UTF-8');
                    $xml .= "<c r=\"{$pos}\"{$style} t=\"inlineStr\"><is><t>{$esc}</t></is></c>";
                }
            }
            $xml .= '</row>';
            $s1Rows[] = $xml;
            $rowIdx1++;
        };

        $periodStr = date('d M Y', strtotime($data['period']['start_date'])) . ' s/d ' . date('d M Y', strtotime($data['period']['end_date']));
        $summary = $data['summary'] ?? [];

        $addRow1([['v' => 'KELOLAGYM - LAPORAN KEUANGAN & OPERASIONAL', 's' => 2]]);
        $addRow1([['v' => "Periode Laporan: {$periodStr}"]]);
        $addRow1([['v' => 'Tanggal Unduh: ' . date('d M Y H:i') . ' WIB']]);
        $addRow1([]);

        // Ringkasan
        $addRow1([['v' => 'RINGKASAN EKSEKUTIF KEUANGAN', 's' => 3]]);
        $addRow1([['v' => 'Total Omzet Periode', 's' => 4], ['v' => (float)($summary['revenue'] ?? 0), 't' => 'num', 's' => 5]]);
        $addRow1([['v' => 'Rata-rata Transaksi', 's' => 4], ['v' => (float)($summary['average_transaction'] ?? 0), 't' => 'num', 's' => 5]]);
        $addRow1([['v' => 'Total Transaksi Terbayar', 's' => 4], ['v' => (int)($summary['transaction_count'] ?? 0), 't' => 'num', 's' => 6]]);
        $addRow1([['v' => 'Pemasukan Non-Tunai (QRIS & Transfer)', 's' => 4], ['v' => (float)($summary['non_cash_total'] ?? 0), 't' => 'num', 's' => 5]]);
        $addRow1([['v' => 'Kas Tunai Fisik', 's' => 4], ['v' => (float)($summary['cash_total'] ?? 0), 't' => 'num', 's' => 5]]);
        $addRow1([['v' => 'Total Presensi (Check-in)', 's' => 4], ['v' => (int)($summary['checkin_count'] ?? 0), 't' => 'num', 's' => 6]]);
        $addRow1([['v' => 'Member Baru Terdaftar', 's' => 4], ['v' => (int)($summary['new_members'] ?? 0), 't' => 'num', 's' => 6]]);
        $addRow1([]);

        // Metode Pembayaran
        $addRow1([['v' => 'DISTRIBUSI METODE PEMBAYARAN', 's' => 3]]);
        $addRow1([['v' => 'Metode', 's' => 1], ['v' => 'Jumlah Transaksi', 's' => 1], ['v' => 'Total Nominal (Rp)', 's' => 1]]);
        foreach (($data['method_stats'] ?? []) as $m) {
            $addRow1([
                ['v' => strtoupper($m['metode_pembayaran'] ?? '-'), 's' => 4],
                ['v' => (int)($m['transaksi_count'] ?? 0), 't' => 'num', 's' => 6],
                ['v' => (float)($m['total_nominal'] ?? 0), 't' => 'num', 's' => 5],
            ]);
        }
        $addRow1([]);

        // Performa Paket
        $addRow1([['v' => 'PERFORMA PAKET LANGGANAN', 's' => 3]]);
        $addRow1([
            ['v' => 'Nama Paket', 's' => 1],
            ['v' => 'Durasi (Hari)', 's' => 1],
            ['v' => 'Harga Satuan (Rp)', 's' => 1],
            ['v' => 'Total Terjual', 's' => 1],
            ['v' => 'Total Omzet (Rp)', 's' => 1],
        ]);
        foreach (($data['packages'] ?? []) as $pkg) {
            $addRow1([
                ['v' => $pkg['nama_paket'] ?? '-', 's' => 4],
                ['v' => (int)($pkg['durasi_hari'] ?? 0), 't' => 'num', 's' => 6],
                ['v' => (float)($pkg['harga'] ?? 0), 't' => 'num', 's' => 5],
                ['v' => (int)($pkg['total_terjual'] ?? 0), 't' => 'num', 's' => 6],
                ['v' => (float)($pkg['total_pendapatan'] ?? 0), 't' => 'num', 's' => 5],
            ]);
        }
        $addRow1([]);

        // Rincian Transaksi
        $addRow1([['v' => 'RINCIAN TRANSAKSI PEMBAYARAN', 's' => 3]]);
        $addRow1([
            ['v' => 'No. Invoice', 's' => 1],
            ['v' => 'Nama Member', 's' => 1],
            ['v' => 'Kode Member', 's' => 1],
            ['v' => 'No. Telepon', 's' => 1],
            ['v' => 'Paket', 's' => 1],
            ['v' => 'Durasi', 's' => 1],
            ['v' => 'Waktu Pembayaran', 's' => 1],
            ['v' => 'Metode', 's' => 1],
            ['v' => 'Jumlah Bayar (Rp)', 's' => 1],
        ]);
        foreach (($data['transactions'] ?? []) as $tx) {
            $addRow1([
                ['v' => $tx['invoice_code'] ?? '-', 's' => 4],
                ['v' => $tx['member_name'] ?? '-', 's' => 4],
                ['v' => $tx['member_code'] ?? '-', 's' => 4],
                ['v' => $tx['member_phone'] ?? '-', 's' => 4],
                ['v' => $tx['nama_paket'] ?? '-', 's' => 4],
                ['v' => ($tx['durasi_hari'] ?? 0) . ' Hari', 's' => 4],
                ['v' => $tx['waktu_format'] ?? $tx['dibayar_pada'] ?? '-', 's' => 4],
                ['v' => $tx['metode_pembayaran'] ?? '-', 's' => 4],
                ['v' => (float)($tx['jumlah_bayar'] ?? 0), 't' => 'num', 's' => 5],
            ]);
        }

        $s1Cols = '<cols><col min="1" max="1" width="22" customWidth="1"/><col min="2" max="2" width="24" customWidth="1"/><col min="3" max="3" width="16" customWidth="1"/><col min="4" max="4" width="16" customWidth="1"/><col min="5" max="5" width="20" customWidth="1"/><col min="6" max="6" width="14" customWidth="1"/><col min="7" max="7" width="20" customWidth="1"/><col min="8" max="8" width="14" customWidth="1"/><col min="9" max="9" width="20" customWidth="1"/></cols>';
        $zip->addFromString('xl/worksheets/sheet1.xml', "<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"yes\"?>\n<worksheet xmlns=\"http://schemas.openxmlformats.org/spreadsheetml/2006/main\">{$s1Cols}<sheetData>" . implode('', $s1Rows) . "</sheetData></worksheet>");

        // Sheet 2: Presensi Member
        $rowIdx2 = 1;
        $s2Rows = [];
        $addRow2 = function(array $cells) use (&$rowIdx2, &$s2Rows, $colLetter): void {
            $xml = "<row r=\"{$rowIdx2}\">";
            foreach ($cells as $cIdx => $cell) {
                $pos = $colLetter($cIdx + 1) . $rowIdx2;
                $style = isset($cell['s']) ? " s=\"{$cell['s']}\"" : '';
                $type = $cell['t'] ?? 'str';
                $val = $cell['v'] ?? '';
                if ($type === 'num') {
                    $xml .= "<c r=\"{$pos}\"{$style}><v>{$val}</v></c>";
                } else {
                    $esc = htmlspecialchars((string)$val, ENT_QUOTES | ENT_XML1, 'UTF-8');
                    $xml .= "<c r=\"{$pos}\"{$style} t=\"inlineStr\"><is><t>{$esc}</t></is></c>";
                }
            }
            $xml .= '</row>';
            $s2Rows[] = $xml;
            $rowIdx2++;
        };

        $addRow2([['v' => 'KELOLAGYM - RIWAYAT PRESENSI MEMBER', 's' => 2]]);
        $addRow2([['v' => "Periode Laporan: {$periodStr}"]]);
        $addRow2([]);
        $addRow2([
            ['v' => 'No', 's' => 1],
            ['v' => 'Waktu Check-In', 's' => 1],
            ['v' => 'Nama Member', 's' => 1],
            ['v' => 'Kode Member', 's' => 1],
            ['v' => 'Gender', 's' => 1],
            ['v' => 'Status Presensi', 's' => 1],
        ]);
        foreach (($data['attendances'] ?? []) as $i => $att) {
            $addRow2([
                ['v' => $i + 1, 't' => 'num', 's' => 6],
                ['v' => ($att['tanggal'] ?? '') . ' ' . ($att['jam'] ?? ''), 's' => 4],
                ['v' => $att['member_name'] ?? '-', 's' => 4],
                ['v' => $att['member_code'] ?? '-', 's' => 4],
                ['v' => $att['gender_label'] ?? '-', 's' => 4],
                ['v' => 'Check-in Berhasil', 's' => 4],
            ]);
        }

        $s2Cols = '<cols><col min="1" max="1" width="8" customWidth="1"/><col min="2" max="2" width="22" customWidth="1"/><col min="3" max="3" width="26" customWidth="1"/><col min="4" max="4" width="16" customWidth="1"/><col min="5" max="5" width="14" customWidth="1"/><col min="6" max="6" width="20" customWidth="1"/></cols>';
        $zip->addFromString('xl/worksheets/sheet2.xml', "<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"yes\"?>\n<worksheet xmlns=\"http://schemas.openxmlformats.org/spreadsheetml/2006/main\">{$s2Cols}<sheetData>" . implode('', $s2Rows) . "</sheetData></worksheet>");

        $zip->close();
        $bin = file_get_contents($tempFile);
        @unlink($tempFile);
        return $bin;
    }

    /**
     * Generator dokumen PDF terformat sesuai standar A4
     */
    private static function generatePdfBinary(array $data): string {
        $periodStr = date('d M Y', strtotime($data['period']['start_date'])) . ' s/d ' . date('d M Y', strtotime($data['period']['end_date']));
        $pdf = new GymReportPdfWriter($periodStr);

        $summary = $data['summary'] ?? [];
        $pdf->summaryBox([
            ['label' => 'Total Omzet Periode', 'value' => $summary['revenue_formatted'] ?? 'Rp 0'],
            ['label' => 'Rata-rata Transaksi', 'value' => $summary['average_transaction_formatted'] ?? 'Rp 0'],
            ['label' => 'Kas Tunai Fisik', 'value' => $summary['cash_total_formatted'] ?? 'Rp 0'],
            ['label' => 'Pemasukan Non-Tunai', 'value' => $summary['non_cash_total_formatted'] ?? 'Rp 0'],
        ]);

        // Metode Pembayaran
        $pdf->sectionTitle('DISTRIBUSI METODE PEMBAYARAN');
        $methodRows = [];
        foreach (($data['method_stats'] ?? []) as $m) {
            $methodRows[] = [
                strtoupper($m['metode_pembayaran'] ?? '-'),
                ($m['transaksi_count'] ?? 0) . ' Transaksi',
                'Rp ' . number_format((float)($m['total_nominal'] ?? 0), 0, ',', '.'),
            ];
        }
        if (empty($methodRows)) {
            $methodRows[] = ['-', '0 Transaksi', 'Rp 0'];
        }
        $pdf->table(['Metode', 'Jumlah Transaksi', 'Total Nominal'], [150, 165, 200], $methodRows);

        // Performa Paket
        $pdf->sectionTitle('PERFORMA PAKET LANGGANAN');
        $pkgRows = [];
        foreach (($data['packages'] ?? []) as $pkg) {
            $pkgRows[] = [
                $pkg['nama_paket'] ?? '-',
                ($pkg['durasi_hari'] ?? 0) . ' Hari',
                $pkg['harga_format'] ?? 'Rp 0',
                ($pkg['total_terjual'] ?? 0) . ' Kali',
                $pkg['total_pendapatan_format'] ?? 'Rp 0',
            ];
        }
        if (empty($pkgRows)) {
            $pkgRows[] = ['-', '-', 'Rp 0', '0 Kali', 'Rp 0'];
        }
        $pdf->table(['Paket', 'Durasi', 'Harga Satuan', 'Terjual', 'Total Omzet'], [140, 75, 100, 80, 120], $pkgRows);

        // Rincian Transaksi
        $pdf->sectionTitle('RINCIAN TRANSAKSI PEMBAYARAN');
        $txRows = [];
        foreach (($data['transactions'] ?? []) as $tx) {
            $txRows[] = [
                $tx['invoice_code'] ?? '-',
                ($tx['member_name'] ?? '-') . ' (' . ($tx['member_code'] ?? '-') . ')',
                $tx['nama_paket'] ?? '-',
                $tx['waktu_format'] ?? '-',
                $tx['metode_pembayaran'] ?? '-',
                $tx['jumlah_bayar_format'] ?? 'Rp 0',
            ];
        }
        if (empty($txRows)) {
            $txRows[] = ['-', 'Tidak ada transaksi pada periode ini', '-', '-', '-', 'Rp 0'];
        }
        $pdf->table(['Invoice', 'Member', 'Paket', 'Waktu', 'Metode', 'Jumlah'], [95, 120, 100, 75, 45, 80], $txRows);

        return $pdf->output();
    }

    /**
     * Alias method reports() untuk kompatibilitas
     */
    public static function reports(): void {
        self::index();
    }
}

/**
 * Helper class PDF A4 murni PHP tanpa vendor library
 */
class GymReportPdfWriter {
    private array $pages = [];
    private string $buf = '';
    private float $y = 780;
    private int $pageNo = 0;
    private string $periodStr;

    public function __construct(string $periodStr) {
        $this->periodStr = $periodStr;
        $this->newPage();
    }

    private function newPage(): void {
        if ($this->pageNo > 0) {
            $this->pages[] = $this->buf;
        }
        $this->pageNo++;
        $this->buf = '';
        $this->y = 790;

        // Header Top Bar Orange
        $this->buf .= "1.000 0.478 0.102 rg\n40 812 515 3.5 re f\n0 0 0 rg\n";
        $this->text(40, 794, 'KELOLAGYM', 15, true);
        $this->text(135, 796, 'LAPORAN RESMI KEUANGAN & OPERASIONAL', 10, false);
        $this->text(40, 782, 'Periode: ' . $this->periodStr, 8.5, false);
        $this->text(480, 782, 'Hal. ' . $this->pageNo, 8.5, false);
        $this->buf .= "0.85 0.85 0.88 RG\n40 774 m 555 774 l S\n0 0 0 RG\n";
        $this->y = 756;
    }

    public function checkSpace(float $needed): void {
        if ($this->y - $needed < 45) {
            $this->newPage();
        }
    }

    public function text(float $x, float $y, string $text, float $size = 9, bool $bold = false): void {
        $font = $bold ? '/F2' : '/F1';
        $text = str_replace(['·', '–', '—', '“', '”', '‘', '’'], ['-', '-', '-', '"', '"', "'", "'"], $text);
        $clean = preg_replace('/[^\x20-\x7E]/', '', $text);
        $clean = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $clean);
        $this->buf .= sprintf("BT %s %.1f Tf %.2f %.2f Td (%s) Tj ET\n", $font, $size, $x, $y, $clean);
    }

    public function sectionTitle(string $title): void {
        $this->checkSpace(32);
        $this->y -= 6;
        $this->buf .= "0.95 0.95 0.96 rg\n";
        $this->buf .= sprintf("40 %.2f 515 16 re f\n", $this->y - 3);
        $this->buf .= "1.000 0.478 0.102 rg\n";
        $this->buf .= sprintf("40 %.2f 3.5 16 re f\n0 0 0 rg\n", $this->y - 3);
        $this->text(48, $this->y, $title, 9.5, true);
        $this->y -= 18;
    }

    public function summaryBox(array $items): void {
        $this->checkSpace(55);
        $count = count($items);
        $w = 515 / $count;

        $this->buf .= "0.96 0.97 0.98 rg\n";
        $this->buf .= sprintf("40 %.2f 515 42 re f\n", $this->y - 34);
        $this->buf .= "0.85 0.85 0.88 RG\n";
        $this->buf .= sprintf("40 %.2f 515 42 re S\n0 0 0 RG\n0 0 0 rg\n", $this->y - 34);

        foreach ($items as $i => $item) {
            $x = 46 + ($i * $w);
            $this->text($x, $this->y - 6, (string)$item['label'], 7.5, false);
            $this->text($x, $this->y - 22, (string)$item['value'], 10.5, true);
            if ($i > 0) {
                $this->buf .= "0.85 0.85 0.88 RG\n";
                $this->buf .= sprintf("%.2f %.2f m %.2f %.2f l S\n0 0 0 RG\n", 40 + ($i * $w), $this->y - 34, 40 + ($i * $w), $this->y + 8);
            }
        }
        $this->y -= 48;
    }

    public function table(array $headers, array $colWidths, array $rows): void {
        $this->checkSpace(38);
        $this->buf .= "0.15 0.17 0.22 rg\n";
        $this->buf .= sprintf("40 %.2f 515 16 re f\n1 1 1 rg\n", $this->y - 11);

        $x = 45;
        foreach ($headers as $i => $h) {
            $this->text($x, $this->y - 7, (string)$h, 8, true);
            $x += $colWidths[$i];
        }
        $this->buf .= "0 0 0 rg\n";
        $this->y -= 13;

        $alt = false;
        foreach ($rows as $row) {
            $this->checkSpace(16);
            if ($alt) {
                $this->buf .= "0.96 0.96 0.97 rg\n";
                $this->buf .= sprintf("40 %.2f 515 14 re f\n0 0 0 rg\n", $this->y - 10);
            }
            $alt = !$alt;

            $x = 45;
            foreach ($row as $i => $cell) {
                $maxChars = max(5, (int)(($colWidths[$i] ?? 50) / 5.2));
                $cellStr = (string)$cell;
                if (mb_strlen($cellStr) > $maxChars) {
                    $cellStr = mb_substr($cellStr, 0, $maxChars - 2) . '..';
                }
                $bold = ($i === 0 || $i === count($row) - 1);
                $this->text($x, $this->y - 6, $cellStr, 7.8, $bold);
                $x += $colWidths[$i];
            }
            $this->y -= 13;
        }
        $this->y -= 8;
    }

    public function output(): string {
        $this->pages[] = $this->buf;
        $numPages = count($this->pages);
        $out = "%PDF-1.4\n";
        $offsets = [0];

        $add = function(string $body) use (&$out, &$offsets): int {
            $idx = count($offsets);
            $offsets[$idx] = strlen($out);
            $out .= "{$idx} 0 obj\n{$body}\nendobj\n";
            return $idx;
        };

        $fontReg = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>';
        $fontBold = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>';

        $pageObjIds = [];
        for ($i = 0; $i < $numPages; $i++) {
            $pageObjIds[] = 5 + ($i * 2);
        }

        $kidsStr = '[' . implode(' 0 R ', $pageObjIds) . ' 0 R]';
        $add('<< /Type /Catalog /Pages 2 0 R >>');
        $add("<< /Type /Pages /Kids {$kidsStr} /Count {$numPages} >>");
        $add($fontReg);
        $add($fontBold);

        for ($i = 0; $i < $numPages; $i++) {
            $contentId = 5 + ($i * 2) + 1;
            $add("<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595.28 841.89] /Resources << /Font << /F1 3 0 R /F2 4 0 R >> >> /Contents {$contentId} 0 R >>");
            $stream = $this->pages[$i];
            $streamLen = strlen($stream);
            $add("<< /Length {$streamLen} >>\nstream\n{$stream}\nendstream");
        }

        $xrefOffset = strlen($out);
        $totalObjs = count($offsets);
        $out .= "xref\n0 {$totalObjs}\n0000000000 65535 f \n";
        for ($i = 1; $i < $totalObjs; $i++) {
            $out .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }
        $out .= "trailer\n<< /Size {$totalObjs} /Root 1 0 R >>\nstartxref\n{$xrefOffset}\n%%EOF";
        return $out;
    }
}

if (!class_exists('ReportsController', false)) {
    class_alias('LaporanController', 'ReportsController');
}
