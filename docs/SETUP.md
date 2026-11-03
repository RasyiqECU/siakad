# Cara Menjalankan SIMORA (Development)

## Prasyarat

- PHP 8.3+, Composer, PostgreSQL 18 lokal
- Node.js 20+, npm

## Backend (Laravel, port 8000)

```powershell
cd backend
composer install                      # sudah dikerjakan
# .env sudah dikonfigurasi (DB=pgsql, database simora)
php artisan key:generate              # jika APP_KEY kosong
php artisan migrate:fresh --seed      # buat tabel + 6 role + akun demo
php artisan serve --host=127.0.0.1 --port=8000
```

### Akun demo (APP_ENV = local)

| Role | Email | Password |
|---|---|---|
| Super Admin | superadmin@simora.test | password |
| Admin Akademik | admin@simora.test | password |
| Dosen | dosen@simora.test | password |
| Mahasiswa | mahasiswa@simora.test | password |
| Bagian Keuangan | keuangan@simora.test | password |
| Pimpinan | pimpinan@simora.test | password |

Seeding demo akun otomatis dilewati bila `APP_ENV=production`.

## Frontend (Next.js 16, port 3000)

```powershell
cd frontend
npm install                           # sudah dikerjakan
# .env.local sudah berisi NEXT_PUBLIC_API_URL=http://127.0.0.1:8000/api/v1
npm run dev                           # atau: npm run build && npm run start
```

Buka http://localhost:3000 → diarahkan ke /login → login dengan akun demo.

## Uji cepat endpoint (curl / PowerShell)

```powershell
# health
Invoke-RestMethod http://127.0.0.1:8000/api/v1/health

# login
(Invoke-RestMethod -Uri http://127.0.0.1:8000/api/v1/auth/login -Method POST `
  -ContentType "application/json" `
  -Body '{"email":"mahasiswa@simora.test","password":"password"}').data.token

# profil (ganti $tok dengan token di atas)
Invoke-RestMethod http://127.0.0.1:8000/api/v1/auth/me -Headers @{ Authorization = "Bearer $tok" }
```

## Catatan

- Guard frontend memakai konvensi `proxy.ts` (Next 16 — `middleware.ts` sudah deprecated).
- Excel export (Phase 6) membutuhkan ekstensi PHP `gd` + `zip` yang belum tersedia pada PHP lokal ini; gunakan Docker atau install ekstensi terlebih dahulu. DomPDF (PDF export) tidak memerlukan keduanya.
