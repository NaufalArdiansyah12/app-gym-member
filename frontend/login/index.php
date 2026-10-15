<?php
// frontend/login/index.php
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - KelolaGym</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <main class="auth">
        <!-- Latar penuh: foto + overlay -->
        <div class="auth__bg" aria-hidden="true">
            <img class="auth__photo" src="img/hero.jpg" alt="">
            <div class="auth__overlay"></div>
        </div>

        <!-- Branding di sisi kiri -->
        <div class="brand">
            <svg class="brand__logo" viewBox="0 0 24 24" aria-hidden="true" focusable="false"
                 fill="none" stroke="currentColor" stroke-width="1.75"
                 stroke-linecap="round" stroke-linejoin="round">
                <path d="M7 12h10M3 9v6m4-9v12M17 6v12m4-9v6M3 12h4m10 0h4"/>
            </svg>
            <span class="brand__name">Kelola<span class="accent">Gym</span></span>
        </div>

        <!-- Kartu login "liquid glass" di sisi kanan -->
        <section class="auth__panel">
            <form id="loginForm" class="auth__form" autocomplete="off">
                <h2 class="auth__title">Masuk Akun</h2>
                <p class="auth__subtitle">Aplikasi Kelola Gym Member</p>

                <div id="alert" class="alert hidden"></div>

                <div class="field">
                    <label for="email">Email / Kode Member</label>
                    <!-- ponytail: id "email" dipertahankan agar kompatibel dengan script.js -->
                    <input type="text" id="email" name="email" placeholder="Email Admin atau Kode Member" required autofocus>
                </div>

                <div class="field">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="Password" required>
                </div>

                <button type="submit" id="btnSubmit" class="btn-login">Login</button>
            </form>

            <p class="auth__footer">&copy; <?php echo date('Y'); ?> KelolaGym &mdash; Kelola Gym Member</p>
        </section>

        <!-- Kata motivasi di atas-tengah foto -->
        <div class="auth__quote">
            <p class="auth__quote-text">&ldquo;Konsistensi hari ini adalah kekuatan esok hari.&rdquo;</p>
            <p class="auth__quote-sub">Setiap kunjungan membawa Anda satu langkah lebih dekat ke versi terbaik diri.</p>
        </div>

        <!-- Tombol aksi di pojok kanan bawah -->
        
    </main>

    <script src="script.js"></script>
</body>
</html>