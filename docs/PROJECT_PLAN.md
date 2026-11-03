# PROJECT PLAN — SIMORA (Sistem Informasi Akademik)

> Versi: 1.0 — Tanggal: 2026-11-02
> Status: Fase Perencanaan (menunggu persetujuan sebelum implementasi)

---

## 1. Ringkasan Eksekutif

SIMORA adalah aplikasi web Sistem Informasi Akademik (SIAKAD) fullstack untuk lingkungan perguruan tinggi. Sistem mencakup 16 modul (Dashboard, Pengguna, Data Master, Mahasiswa, Dosen, Kurikulum & Mata Kuliah, KRS, Jadwal, Presensi, Nilai, KHS, Transkrip, Pembayaran, Pengumuman, Laporan, Pengaturan Sistem) dengan kontrol akses berbasis role (RBAC) yang memuat 6 role: **Super Admin, Admin Akademik, Dosen, Mahasiswa, Bagian Keuangan, Pimpinan**.

## 2. Analisis Kondisi Project Saat Ini

Hasil pemeriksaan repository `C:\Users\Pongo\Documents\SIMORA`:

| Elemen | Kondisi | Tindakan |
|---|---|---|
| `frontend/` | Scaffolding Next.js 16.3.0 + React 19.2.8 + Tailwind v4 (hanya `app/layout.tsx`, `app/page.tsx`). Ada `.env.local` → `NEXT_PUBLIC_API_URL=http://127.0.0.1:8000/api/v1`, serta `AGENTS.md`/`CLAUDE.md` otomatis dari Next dev | **Dipertahankan sebagai basis**; dependensi design-system Adminto ditambahkan. File bawaan Next tidak dihapus tanpa alasan |
| `backend/` | Laravel 13.8 (skeleton), PHP ^8.3, Sanctum 4 ter-install + migration `personal_access_tokens` sudah published. Route API baru `/v1/health`. Model User default | **Dipertahankan sebagai basis**; tambah spatie/laravel-permission, dompdf, excel. `.env` sudah pgsql database `simora` |
| `design template/` | Adminto-Next.js v1.1.0 (Bootstrap 5 + react-bootstrap + SCSS + ApexCharts + react-hook-form). Versi TS lengkap: 7 varian layout sidebar/navbar, halaman dashboard/tables/forms/charts/ui/auth | **Referensi desain & sumber komponen** — komponen + SCSS tema disalin ke `frontend/` |
| Environment | PHP 8.5.9 ✔, PostgreSQL 18.4 ✔ | Sesuai kebutuhan Laravel 13 |

**Keputusan arsitektur (disetujui user):**
1. **UI stack**: adopsi penuh design system Adminto — Bootstrap 5, react-bootstrap, SCSS tema Adminto, ApexCharts, react-toastify, simplebar, choices.js.
2. **Struktur folder mengikuti perintah awal**: monorepo `frontend/` + `backend/` + `docs/` (setara `siakad/…`, sudah berada di root). **Tidak diubah.**
3. **Export dokumen**: DomPDF (KRS, KHS, transkrip, laporan PDF) + Maatwebsite/Excel (Excel).
4. Next.js 16 App Router + TypeScript tetap; **React Hook Form + Zod** untuk form/validasi; **TanStack Query** untuk server state; **Axios** untuk API client (sesuai spesifikasi awal). Recharts digantikan ApexCharts karena design system Adminto berbasis ApexCharts.

## 3. Arsitektur Sistem

```
┌──────────────────────────────┐     REST /api/v1 (Bearer Token Sanctum)    ┌──────────────────────────────┐
│  FRONTEND (Next.js 16)       │ ─────────────────────────────────────────▶ │  BACKEND (Laravel 13)        │
│  App Router + TS             │                                            │  Route → Middleware (auth,   │
│  - UI Adminto (Bootstrap/SCSS│ ◀──────── JSON {success, data, meta} ───── │  permission) → FormRequest → │
│    + ApexCharts)             │                                            │  Controller → Service →      │
│  - TanStack Query + Axios    │                                            │  Model → APIResource         │
│  - RHF + Zod                 │                                            │  - Policy/Permission gate    │
└──────────────────────────────┘                                            └───────────────┬──────────────┘
                                                                                            │
                                                                                      PostgreSQL 18
```

Prinsip:
- **Frontend** hanya UI/UX dan komunikasi API. Business logic penting (validasi batas SKS, bentrok jadwal, prasyarat, perhitungan nilai/IPS/IPK, otorisasi) **divalidasi ulang di backend** — frontend hanya melakukan validasi pasif untuk UX.
- **Backend**: Flow `Routes → Middleware (auth:sanctum + permission) → FormRequest → Controller (tipis) → Service (business logic + DB transaction) → APIResource (transform)`.
- Semua response API membungkus hasil operasi service layer dengan format `success / message / data` (lihat API_SPECIFICATION.md §1).

### 3.1 Struktur Folder Frontend

```
frontend/
├── app/                          # Next.js App Router
│   ├── (auth)/login/             # desain halaman login Adminto
│   ├── (admin)/                  # layout sidebar+topbar Adminto, dilindungi guard auth
│   │   ├── dashboard/            # konten bergantung role (fetch /me dan /dashboard/summary)
│   │   ├── users/                # pengguna + role-permission (Super Admin)
│   │   ├── master/               # fakultas, jurusan/prodi, tahun akademik, semester, ruang, gedung, status
│   │   ├── mahasiswa/, dosen/    # daftar data + detail (biodata, riwayat) dengan [id]
│   │   ├── kurikulum/, mata-kuliah/
│   │   ├── krs/                  # mahasiswa isi; dosen wali approve; admin monitoring; cetak PDF
│   │   ├── jadwal/               # berdasarkan mahasiswa/dosen/ruang
│   │   ├── presensi/
│   │   ├── nilai/
│   │   ├── khs/, transkrip/      # KHS/transkrip mahasiswa + cetak PDF
│   │   ├── pembayaran/           # tagihan + pembayaran + validasi keuangan
│   │   ├── pengumuman/
│   │   ├── laporan/              # filter + export Excel/PDF
│   │   └── pengaturan/
│   └── (tidak ada /api)          # semua data bisnis ke backend API
├── components/
│   ├── layout/       # LeftSideBar, TopBar, TopBar components (dari Adminto)
│   ├── ui/           # komponen Adminto (Card, Badge, Button, Alert, dsb.)
│   ├── form/         # TextInput, Select, DatePicker, Dropzone (dari Adminto)
│   └── shared/       # DataTable wrapper, ConfirmDialog, EmptyState, StatCard, ChartCard, Pagination
├── context/          # useLayoutContext, useNotificationContext (Adminto)
├── hooks/            # useToggle, useViewPort (Adminto) + hooks data TanStack per modul
├── helpers/          # Menu.ts (item sidebar dinamis per role), formatters
├── services/api/     # axios client + modul: auth.ts, user.ts, master.ts, krs.ts, nilai.ts, dsb.
├── types/            # kontrak tipe model & response API
├── assets/           # scss tema Adminto, images, fonts (dari template)
├── utils/            # formatters tanggal, angka, mapping status → warna badge
├── middleware.ts     # guard halaman: tanpa token → redirect /login
├── AGENTS.md, CLAUDE.md, package.json, tsconfig.json, next.config.ts, postcss.config.mjs, eslint.config.mjs
```

Catatan: Tailwind v4 tetap ter-install (bawaan) dan di-cache untuk kebutuhan utilitas kecil; **sistem visual utama adalah Bootstrap 5 + SCSS Adminto**.

### 3.2 Struktur Folder Backend

```
backend/
├── app/
│   ├── Enums/                     # AcademicProgram, CourseType, AttendanceStatus, GradeStatus, PaymentStatus, dsb.
│   ├── Http/
│   │   ├── Controllers/Api/V1/    # tipis — panggil Service, return Resource
│   │   ├── Requests/Api/V1/       # StoreXxxRequest / UpdateXxxRequest tiap modul
│   │   ├── Resources/Api/V1/      # standardisasi response
│   │   └── Middleware/            # EnsureUserIsActive, ForceJsonResponse, dsb.
│   ├── Services/                  # KrsService, ScheduleService, AttendanceService, GradeService, dsb.
│   ├── Services/Reports/          # query laporan + export DomPDF & Maatwebsite Excel
│   ├── Models/                    # Eloquent + relasi + casting + softDeletes
│   ├── Policies/                  # CourseClassPolicy (dosen hanya kelas diampu), StudentPolicy, dsb.
│   ├── Observers/                 # AuditLogObserver (log aktivitas penting)
│   └── Console/Commands/          # siakad:seed-demo (dev-only)
├── bootstrap/app.php              # registrasi middleware & alias
├── config/siakad.php              # batas SKS default, bobot nilai, dsb. (bisa dioverride tabel settings)
├── database/
│   ├── migrations/                # urutan sesuai §3 DATABASE_DESIGN.md
│   ├── factories/ & seeders/      # DatabaseSeeder (dev): akun 6 role + data master + data akademik demo
│   └── seeders/PermissionsSeeder.php
├── routes/api.php                 # prefix /api/v1
└── tests/Feature/                 # PHPUnit per modul
```

## 4. Role dan Permission

Struktur RBAC menggunakan spatie/laravel-permission (tabel `roles`, `permissions`, `model_has_roles`, dsb.).

| Domain | Super Admin | Admin Akademik | Dosen | Mahasiswa | Keuangan | Pimpinan |
|---|---|---|---|---|---|---|
| users (CRUD + role) | ✔ | create/read/update | ✕ | ✕ | ✕ | ✕ |
| master data (fak. jur, prodi, tahun, semester, ruang, gedung, status) | ✔ | ✔ | read | ✕ | ✕ | read |
| mahasiswa / dosen | ✔ | ✔ | baca diri & mahasiswa wali/ampu | baca diri | ✕ | read |
| kurikulum / mata kuliah | ✔ | ✔ | read | read | ✕ | read |
| jadwal | ✔ | ✔ | read (kelas diampu) | read (kelas diikuti) | ✕ | read |
| KRS | ✔ | kelola periode & monitoring | setujui (sebagai wali) | isi/edit saat periode | ✕ | read |
| presensi | ✔ | read | input kelola kelas diampu | lihat diri | ✕ | read (laporan) |
| nilai | ✔ | read | input/kelola kelas diampu | lihat (published) | ✕ | read |
| pembayaran | ✔ | ✕ | ✕ | lihat tagihan + riwayat diri | ✔ kelola & validasi | read |
| pengumuman | ✔ | ✔ | read | read | read | read |
| laporan | ✔ | ✔ | ✕ | ✕ | laporan keuangan | ✔ |
| pengaturan sistem | ✔ | ✕ | ✕ | ✕ | ✕ | ✕ |

Aturan proteksi data (anti IDOR):
- Dosen hanya dapat menginput nilai/presensi pada `course_class` yang ada di `course_class_lecturers` miliknya (Policy).
- Mahasiswa hanya dapat mengakses KRS/nilai/kartu/tagihan miliknya sendiri (`student_id === auth()->user()->student->id`).
- Semua endpoint write dijaga dengan middleware `permission:` + Policy pada model.

## 5. Modul dan Entitas Terkait

| # | Modul | Entitas utama | DB Table |
|---|---|---|---|
| 1 | Autentikasi & Audit | users, roles, permissions, audit_logs | lihat DATABASE_DESIGN.md |
| 2 | Data Master | faculties, departments, study_programs, academic_years, semesters, academic_statuses, buildings, class_rooms | |
| 3 | Kurikulum & Mata Kuliah | curriculums, courses, curriculum_courses, course_prerequisites | |
| 4 | Mahasiswa & Dosen | students, lecturers | |
| 5 | Kelas & Jadwal | course_classes, course_class_lecturers, schedules | |
| 6 | KRS | study_plans, study_plan_details | |
| 7 | Presensi | attendances, attendances_details | |
| 8 | Nilai, KHS, Transkrip | grade_components, grades, grade_logs | |
| 9 | Pembayaran | tuition_bills, payments | |
| 10 | Dashboard | endpoint ringkasan (aggregate query) | |
| 11 | Pengumuman | announcements, announcement_targets | |
| 12 | Laporan & Export | Services/Reports (DomPDF + Excel) | |
| 13 | Pengaturan Sistem | settings (key-value, json) | |

## 6. Tech Stack dan Dependensi

### Backend (composer)
| Paket | Kegunaan |
|---|---|
| laravel/framework ^13.8, laravel/sanctum ^4.0, phpunit ^12.5 | sudah ter-install |
| **spatie/laravel-permission** | RBAC (roles & permissions), versi yang dinyatakan kompatibel dengan Laravel 13 |
| **barryvdh/laravel-dompdf** | cetak KRS, KHS, transkrip, laporan pdf |
| **maatwebsite/excel** | export laporan Excel / CSV |

### Frontend (npm, selain bawaan scaffolding)
| Paket | Kegunaan |
|---|---|
| bootstrap ^5.3.8, react-bootstrap ^2.10.10, sass | design system Adminto |
| @tanstack/react-query, axios | data fetching & server state |
| react-hook-form, zod, @hookform/resolvers | form & validasi (frontend) |
| apexcharts, react-apexcharts | grafik dashboard |
| react-toastify, react-sweetalert2 | notifikasi & konfirmasi |
| @tanstack/react-table, simplebar-react | tabel data & scrollbar |
| choices.js / react-select, react-flatpickr, dayjs | input form (pencarian, date filter) |
| lucide-react, @iconify/react | ikon |
| jsPDF (opsional) | preview pdf bila dibutuhkan di client |

Catatan kompatibilitas: versi final `spatie/laravel-permission` dan `maatwebsite/excel` akan diverifikasi terhadap Laravel 13/PHP 8.4 saat proses `composer require`, lalu dicatat di README.

### Infrastruktur
- `docker-compose.yml` + Dockerfile (frontend, backend, postgres, redis opsional) akan dibuat pada **Phase 7** agar tidak mencampur state development saat ini (environment sudah jalan tanpa Docker).
- `docker-compose.override.yml` untuk konfigurasi dev (bind mount, hot reload) dipisah dari compose production.

## 7. Fase Implementasi

| Fase | Isi | Output utama |
|---|---|---|
| DONE (perencanaan) | Analisis + dokumen PROJECT_PLAN, DATABASE_DESIGN, API_SPECIFICATION, ERD | `docs/*.md` |
| **DONE (Phase 1 — Foundation)** ✔ | Design system Adminto terpasang di `frontend/` (layout sidebar/topbar, login page, guard `proxy.ts`), TanStack Query + Axios + Bearer token, seeder 6 role + akun demo, `AuthController` (login/me/logout) + middleware `EnsureUserIsActive`, CORS whitelist, verifikasi FE↔BE end-to-end (login → /me → logout → 401 → CORS 204) | Frontend :3000 ↔ Backend :8000 terhubung |
| **Phase 1 — Foundation** | Pasang design system Adminto ke `frontend/` (layout, theme, login page), setup TanStack Query + Axios + token handling, verifikasi CORS Sanctum, koneksi login FE↔BE | Halaman login + layout dasar connected |
| **Phase 2 — DB & Auth** | Migration seluruh tabel, factory + seeder master, autentikasi Sanctum, RBAC Spatie, middleware, audit log, Profil & User Management | ERD → tabel nyata, login/CRUD user berfungsi |
| **DONE (Phase 2 — DB & Auth)** ✔ | Seluruh 30+ tabel sesuai ERD dimigrasikan + 33 model + 16 enum, PermissionsSeeder (96 permission + matrix RBAC 6 role + Gate::before super admin), audit log (observer + service + API), User Management CRUD lengkap (activate/deactivate/reset-password/proteksi diri & super admin terakhir), Role API (+sync permission), Profil API (+ganti password, avatar upload), audit-logs endpoint; UI: `/pengguna` (CRUD + filter + modal + confirm), `/audit-log`, `/profil`, menu gating per permission (`filterMenuByPermission`); build hijau + 16/16 test PHPUnit lulus + 25/25 curl verifikasi | DB lengkap, RBAC aktif, halaman pengguna berfungsi |
| **Phase 3 — Master Data** | CRUD fakultas → prodi → tahun → semester → ruang → gedung → status → mata kuliah → kurikulum → prasyarat → mahasiswa → dosen | Semua master lengkap + UI table/form + tests |
| **DONE (Phase 3 — Master Data)** ✔ | 12 resource CRUD nyata (fakultas→kurikulum+prasyarat, mahasiswa+dosen dengan auto akun login), service rules (single aktif tahun/semester, recompute total SKS, prasyarat validasi), MasterDataDemoSeeder (idempoten, skip production), UI engine generik `ResourceCrudPage` + 12 halaman master dengan filter cascade + gating permission, 27/27 testPHPUnit lulus + verifikasi curl 12 resource lulus | Master data lengkap berfungsi end-to-end |
| **Phase 4 — Academic** | Kelas kuliah, jadwal + validasi bentrok, presensi, nilai + grade components, KRS + workflow approval, KHS, transkrip, pengumuman | Modul akademik end-to-end |
| **Phase 5 — Finance** | Tagihan, pembayaran manual (bukti upload), validasi keuangan, riwayat & status | Modul keuangan |
| **Phase 6 — Dashboard & Reports** | Dashboard per role (stat card + chart), laporan + filter + export PDF/Excel, pengaturan sistem | Beranda dinamis per role |
| **Phase 7 — Testing & Finalization** | PHPUnit Feature terseluruh, security check, optimasi query, responsive check, docker-compose, dokumentasi final, caching | Rilis |

## 8. Keamanan

- Password hashing bcrypt (Laravel default), minimal 8 karakter.
- Autentikasi Sanctum (SPA + token), token di-revoke saat logout.
- CORS whitelist (`http://localhost:3000` dev, domain prod konfigurasi), stateful domain sesuai Sanctum.
- Validasi input di FormRequest, proteksi SQL injection via Eloquent/Query Builder (tanpa raw SQL berbasis input).
- Rate limit endpoint sensitif (`login` khusus 5/menit).
- Proteksi IDOR dengan Policy (lihat §4).
- Upload file: whitelist mime & maksimal size per keperluan (gambar profil, bukti pembayaran, logo).
- Audit log (aktivitas, subjek, ip, user_agent, perubahan data properties via Observer).
- Environment variable via `.env`; tidak ada kredensial dalam kode.

## 9. Risiko dan Mitigasi

| Risiko | Mitigasi |
|---|---|
| ~~Kompatibilitas spatie/laravel-permission & maatwebsite/excel terhadap Laravel 13~~ | **Terverifikasi**: `spatie/laravel-permission ^8.3` + `barryvdh/laravel-dompdf ^3.1` terpasang (Phase 1). `maatwebsite/excel` **ditunda ke Phase 6** karena PHP lokal (C:\\PHP) tidak memiliki ekstensi `gd`/`zip` — sebelum export Excel, install ekstensi tersebut atau gunakan Docker. |
| Konversi UI dari template Adminto (Next 16 App Router) 1:1 | Salin komponen layout + SCSS utama secara bertahap; jalankan `next build` setelah setiap tahap. |
| Next.js 16 berbeda konvensi (lihat `AGENTS.md` di frontend) | Konvensi terverifikasi: file guard memakai **`proxy.ts`** (bukan `middleware.ts`, sudah deprecated). |
| Perhitungan uang & nilai menimbulkan presisi | Cast `decimal` (bukan float) di model + validasi numeric + test perhitungan. |
| Validasi bentrok jadwal kompleks | Cek overlap dilakukan di Service dengan DB transaksi + query rentang waktu + tests. |

## 10. Akun Demo (development)

| Role | Email | Password |
|---|---|---|
| Super Admin | `superadmin@simora.test` | `password` |
| Admin Akademik | `admin@simora.test` | `password` |
| Dosen | `dosen@simora.test` | `password` |
| Mahasiswa | `mahasiswa@simora.test` | `password` |
| Keuangan | `keuangan@simora.test` | `password` |
| Pimpinan | `pimpinan@simora.test` | `password` |

Di-seed hanya ketika `APP_ENV != production`.

## 11. Langkah Ini (Tahap Perencanaan)

1. ✔ Pemeriksaan struktur folder.
2. ✔ Analisis kebutuhan sistem.
3. ✔ Penentuan arsitektur frontend, backend, dan database.
4. ✔ Dokumen `PROJECT_PLAN.md` (file ini).
5. ✔ Dokumen `DATABASE_DESIGN.md` + ERD Mermaid.
6. ✔ Dokumen `API_SPECIFICATION.md`.
7. ✔ Struktur folder yang akan digunakan (§3).
8. ✔ Daftar modul, dependensi, dan tahapan pengerjaan (§5–§7).
9. **⏳ Menunggu persetujuan user** sebelum masuk Phase 1.

---

> Setelah Anda setuju, langkah berikutnya: **Phase 1 (Foundation)** — setup design system Adminto di `frontend/`, autentikasi Sanctum, koneksi FE↔BE, dan verifikasi dependensi composer (spatie, dompdf, excel).
