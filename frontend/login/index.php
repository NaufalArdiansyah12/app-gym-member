<?php
// frontend/login/index.php
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk - Kelola Gym Member</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="login-wrapper">
        <!-- Split Sisi Kiri: Informasi Aplikasi & Fitur -->
        <div class="login-info-side">
            <div class="info-bg-glow" aria-hidden="true"></div>
            
            <div class="brand-badge">
                <div class="brand-icon-box" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M7 12h10M3 9v6m4-9v12M17 6v12m4-9v6M3 12h4m10 0h4"/>
                    </svg>
                </div>
                <span>KELOLA <strong class="accent">GYM</strong></span>
            </div>

            <div class="info-content">
                <span class="info-tag">SISTEM GYM MEMBER & PRESENSI</span>
                <h1 class="info-title">Kelola Kehadiran & Langganan Gym Lebih Praktis</h1>
                <p class="info-desc">
                    Platform digital untuk memantau status keanggotaan, riwayat presensi scan QR meja resepsionis, dan pengelolaan paket membership terpadu.
                </p>

                <div class="feature-list">
                    <div class="feature-item">
                        <div class="feature-icon orange" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M14 3h6v18h-6M3 12h12m-5-5 5 5-5 5"/></svg>
                        </div>
                        <div>
                            <strong>Presensi Scan QR Admin</strong>
                            <p>Tunjukkan kartu member digital saat tiba di gym untuk scan cepat.</p>
                        </div>
                    </div>

                    <div class="feature-item">
                        <div class="feature-icon green" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="m12 3 9 5v8l-9 5-9-5V8l9-5Zm-9 5 9 5 9-5M12 13v8M7.5 5.5l9 5"/></svg>
                        </div>
                        <div>
                            <strong>Status Membership Real-time</strong>
                            <p>Pantau sisa masa aktif dan paket langganan secara transparan.</p>
                        </div>
                    </div>

                    <div class="feature-item">
                        <div class="feature-icon yellow" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="9" cy="8" r="3"/><path d="M3 21v-2a6 6 0 0 1 12 0v2M16 5a3 3 0 0 1 0 6m2 3a5 5 0 0 1 3 5v2"/></svg>
                        </div>
                        <div>
                            <strong>Portal Admin & Member</strong>
                            <p>Hak akses terpisah untuk operasional gym dan member fitness.</p>
                        </div>
                    </div>
                </div>

                <!-- Demo Account Selector Chips -->
                <div class="demo-box">
                    <span class="demo-label">AKUN UJI COBA (KLIK UNTUK OTOMATIS MENGISI):</span>
                    <div class="demo-buttons">
                        <button type="button" id="btnDemoAdmin" class="demo-btn">
                            <span class="demo-role admin">ADMIN</span>
                            <span class="demo-acc">admin@gym.com</span>
                        </button>
                        <button type="button" id="btnDemoUser" class="demo-btn">
                            <span class="demo-role member">MEMBER</span>
                            <span class="demo-acc">user@gym.com</span>
                        </button>
                    </div>
                </div>
            </div>

            <div class="info-footer">
                <span>© <?= date('Y') ?> Kelola Gym App · Akses Terenkripsi</span>
            </div>
        </div>

        <!-- Split Sisi Kanan: Form Login -->
        <div class="login-form-side">
            <div class="form-card">
                <div class="form-header">
                    <h2>Masuk ke Akun</h2>
                    <p class="subtitle">Masukkan email dan kata sandi terdaftar Anda.</p>
                </div>

                <div id="alert" class="alert hidden" role="alert"></div>

                <form id="loginForm" novalidate>
                    <div class="form-group">
                        <label for="email">ALAMAT EMAIL</label>
                        <div class="input-wrap">
                            <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                                <polyline points="22,6 12,13 2,6"/>
                            </svg>
                            <input type="email" id="email" name="email" placeholder="nama@email.com" required autofocus autocomplete="email">
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="label-row">
                            <label for="password">KATA SANDI</label>
                            <button type="button" id="btnTogglePassword" class="link-btn" tabindex="-1">Lihat</button>
                        </div>
                        <div class="input-wrap">
                            <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                                <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                            </svg>
                            <input type="password" id="password" name="password" placeholder="••••••••" required autocomplete="current-password">
                        </div>
                    </div>

                    <button type="submit" id="btnSubmit">
                        <span>Masuk Sekarang</span>
                        <svg class="btn-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                            <path d="m9 5 7 7-7 7"/>
                        </svg>
                    </button>
                </form>

                <div class="form-footer-tip">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <circle cx="12" cy="12" r="10"/>
                        <line x1="12" y1="16" x2="12" y2="12"/>
                        <line x1="12" y1="8" x2="12.01" y2="8"/>
                    </svg>
                    <span>Belum terdaftar? Pendaftaran member baru dapat dilakukan langsung melalui meja admin gym.</span>
                </div>
            </div>
        </div>
    </div>

    <script src="script.js"></script>
</body>
</html>
