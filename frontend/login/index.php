<?php
// frontend/login/index.php
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - KelolaGym</title>
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'%3E%3Cdefs%3E%3ClinearGradient id='g' x1='0%25' y1='0%25' x2='100%25' y2='100%25'%3E%3Cstop offset='0%25' stop-color='%23ff8a3d'/%3E%3Cstop offset='100%25' stop-color='%23ff6a00'/%3E%3C/linearGradient%3E%3C/defs%3E%3Crect width='32' height='32' rx='8' fill='url(%23g)'/%3E%3Cg transform='translate(4,4)' fill='none' stroke='%23ffffff' stroke-width='2.2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M7 12h10M3 9v6m4-9v12M17 6v12m4-9v6M3 12h4m10 0h4'/%3E%3C/g%3E%3C/svg%3E">
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
                    <div class="password-wrapper">
                        <input type="password" id="password" name="password" placeholder="Password" required>
                        <button type="button" id="togglePassword" class="password-toggle" aria-label="Lihat password" title="Lihat password" aria-pressed="false">
                            <svg class="icon-eye" viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                            <svg class="icon-eye-off hidden" viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                                <line x1="1" y1="1" x2="23" y2="23"></line>
                            </svg>
                        </button>
                    </div>
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