<?php
// frontend/admin/dashboard/index.php
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Kelola Gym</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="dashboard-card">
        <div class="header">
            <div>
                <h2>Dashboard Admin</h2>
                <p class="subtitle">Kelola Sistem Gym</p>
            </div>
            <button id="btnLogout" class="btn-logout">Keluar</button>
        </div>

        <div class="info-grid">
            <div class="info-item">
                <div class="info-label">Nama</div>
                <div class="info-value" id="userName">-</div>
            </div>
            <div class="info-item">
                <div class="info-label">Email</div>
                <div class="info-value" id="userEmail">-</div>
            </div>
            <div class="info-item">
                <div class="info-label">Peran</div>
                <div class="info-value"><span class="badge" id="userRole">Admin</span></div>
            </div>
        </div>
    </div>

    <script src="script.js"></script>
</body>
</html>
