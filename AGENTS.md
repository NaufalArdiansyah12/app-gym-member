# AGENTS.md

## Overview & Architecture
- Stack: Native PHP 8.x backend API + Vanilla PHP/HTML/JS frontend. No framework, no build step.
- Backend router: `backend/public/index.php` routes to `backend/controllers/`.
- Frontend server: `frontend/` directory served on port 3000; root redirects to `/login/`. Post-login directs to `/admin/` or `/user/`.
- Auth flow: JSON POST to backend `/api/login`; backend starts PHP session; frontend stores user payload in `localStorage`.

## Dev & Verification Commands
- Backend dev server: `npm run dev:backend` (`php -S 127.0.0.1:8000 -t backend/public`)
- Frontend dev server: `npm run dev:frontend` (`php -S 127.0.0.1:3000 -t frontend`)
- Run tests: `npm run test:backend` (`php backend/test.php`)
- Single test check: `php backend/test.php`
- Database migrate & seed: `npm run db:migrate` (`mysql -u root < database/schema.sql`)

## Database & Environment Quirks
- Database name: `db-kelola-gym` (hyphenated; quote with backticks in SQL).
- Connection config: `backend/config/database.php` via `getDB()`. Env overrides: `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`, `DB_PORT`. Default: `127.0.0.1:3306`, root, empty password.
- Seed credentials:
  - Admin: `admin@gym.com` / `admin123` (role: `admin`)
  - Member: `user@gym.com` / `user123` (role: `user`)

## Conventions & Gotchas
- Page structure convention: setiap pembuatan halaman baru di bawah `frontend/admin/` atau `frontend/user/`, buat subfolder tersendiri dengan nama halaman (misal `admin/dashboard/`). Setiap folder halaman WAJIB memiliki 3 file terpisah: `index.php`, `style.css`, dan `script.js`.
- No Vite/React build: frontend migrated to native PHP/JS. Do not invoke node bundlers.
- Testing pattern: no PHPUnit/framework; tests use native PHP `assert()`.
- Simplification ceiling: mark intentional simplifications with `// ponytail: [rationale]` comments.
- Dual Environment (Apache LAMPP vs PHP dev-server): backend router mendukung path port 8000 (`/api/login`) dan subfolder LAMPP (`.../backend/public/index.php/api/login`). Frontend API URL otomatis deteksi port 3000 atau relative path Apache. Gunakan path navigasi relative (`../`, `../../`) agar jalan di kedua environment.
