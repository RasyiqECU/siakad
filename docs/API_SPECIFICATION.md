# API SPECIFICATION — SIMORA

> Versi: 1.0 · Base URL: `http://127.0.0.1:8000/api/v1`
> Auth: Laravel Sanctum (Bearer Token). Semua endpoint (kecuali Auth) memerlukan header `Authorization: Bearer <token>` dan `Accept: application/json`.

---

## 1. Konvensi Umum

### 1.1 Format Response

```json
// Sukses tunggal
{ "success": true, "message": "string", "data": { ... } }

// Sukses list + paginasi
{ "success": true, "message": "string", "data": [ ... ],
  "meta": { "current_page": 1, "last_page": 5, "per_page": 15, "total": 62, "from": 1, "to": 15 },
  "links": { "first": "...", "last": "...", "prev": null, "next": "..." }
}

// Error validasi (422)
{ "success": false, "message": "Data yang diberikan tidak valid.", "errors": { "field": ["msg"] } }

// Error lain (401/403/404/500)
{ "success": false, "message": "string" }
```

### 1.2 Konvensi Query Parameter (list endpoint)

- `?page=2` — paginasi (default 15 item).
- `?search=kata` — pencarian (kolom didukung bervariasi per endpoint).
- `?sort_by=name&sort_dir=asc` — sorting (whitelist per endpoint).
- `?study_program_id=3&semester_id=5&entry_year=2024&status=pending` — filter sesuai modul.
- `?per_page=50` — override ukuran halaman (maksimal 100).

### 1.3 HTTP Status

| Kode | Arti |
|---|---|
| 200 / 201 | sukses |
| 204 | sukses tanpa isi (delete) |
| 401 | token tidak valid / expired |
| 403 | role/permission tidak memenuhi, user nonaktif, atau bukan pemilik data (guard IDOR) |
| 404 | data tidak ditemukan |
| 422 | validasi gagal / business rule gagal |
| 429 | rate limit |
| 500 | kesalahan server (di-log) |

### 1.4 Error Business Rule

Gagal rule bisnis memakai 422 dengan penanda rule:

```json
{ "success": false,
  "message": "Mata kuliah MTK101 tidak dapat diambil.",
  "data": { "rule": "bentrok_jadwal", "detail": "Bentrok dengan jadwal Kamis 08:00-10:00." } }
```

Rule yang digunakan: `masa_krs_berakhir`, `bentrok_jadwal`, `bentrok_dosen`, `bentrok_ruangan`, `batas_sks`, `prasyarat_belum_lulus`, `sudah_ditempuh`, `kelas_penuh`, `kelas_tutup`.

---

## 2. Auth

| Method | Endpoint | Deskripsi | Permission |
|---|---|---|---|
| POST | `/auth/login` | Login, mengembalikan token Sanctum | public, rate limit 5/menit |
| POST | `/auth/logout` | Revoke token aktif | auth |
| GET | `/me` | Profil user, roles, daftar permissions, data student/lecturer jika ada | auth |

`POST /auth/login` — body: `{ "email": "...", "password": "...", "device_name": "browser" }`

Response `data`:
```json
{ "token": "1|abc...",
  "user": { "id": 7, "name": "...", "roles": ["mahasiswa"],
            "permissions": ["krs.create", "krs.read"],
            "student": { "student_number": "2024-0001", "study_program": {...} } } }
```

---

## 3. Modul Endpoint

### 3.1 Dashboard

| Method | Endpoint | Deskripsi |
|---|---|---|
| GET | `/dashboard` | Ringkasan otomatis sesuai role user |
| GET | `/dashboard/admin` | Statistik mahasiswa, dosen, prodi, kelas, MK + grafik jumlah mahasiswa per tahun akademik + aktivitas terbaru + pengumuman |
| GET | `/dashboard/mahasiswa` | IPK/IPS, SKS semester aktif, status KRS, tagihan, jadwal hari ini, pengumuman |
| GET | `/dashboard/dosen` | Kelas diampu, jadwal hari ini, KRS menunggu persetujuan (sebagai wali), pengingat presensi/nilai |
| GET | `/dashboard/keuangan` | Total tagihan/lunas/belum lunas, pembayaran pending, grafik pembayaran per semester |
| GET | `/dashboard/pimpinan` | Rekap statistik seluruh sistem, distribusi IPK per prodi/angkatan, navigasi laporan |

### 3.2 Pengguna & Profil

| Method | Endpoint | Permission |
|---|---|---|
| GET/POST/PATCH/DELETE | `/users`, `/users/{id}` — filter: `?search&role&is_active` | `users.read/create/update/delete` |
| PATCH | `/users/{id}/activate`, `/users/{id}/deactivate` | `users.update` |
| PATCH | `/users/{id}/reset-password` | `users.update` |
| GET | `/roles` — daftar role + permissions-nya | `users.read` |
| PUT | `/roles/{id}/permissions` — sinkronisasi permission role (role & permission management) | `users.update` |
| GET/PATCH | `/profile` — profil sendiri (nama, avatar, ganti password) | auth |
| GET | `/audit-logs?user_id` | `users.read` |

### 3.3 Master Data

Pola CRUD seragam: `GET /{base}` (list paginated), `POST /{base}`, `GET /{base}/{id}`, `PATCH /{base}/{id}`, `DELETE /{base}/{id}` (soft delete).

| Resource | Endpoint base | Permission |
|---|---|---|
| Fakultas | `/faculties` | `faculties.*` |
| Jurusan | `/departments` | `departments.*` |
| Program Studi | `/study-programs` | `study_programs.*` |
| Tahun Akademik | `/academic-years` | `academic_years.*` |
| Semester | `/semesters` — field `academic_year_id`, `krs_start`, `krs_end`, `is_active`; aksi `POST /semesters/{id}/activate` | `semesters.*` |
| Status Akademik | `/academic-statuses` | `academic_statuses.*` |
| Gedung | `/buildings` | `buildings.*` |
| Ruang Kelas | `/class-rooms` | `class_rooms.*` |
| Dosen | `/lecturers` | `lecturers.*` |
| Mahasiswa | `/students` | `students.*` |
| Mata Kuliah | `/courses` | `courses.*` |
| Kurikulum | `/curriculums` | `curriculums.*` |
| Kurikulum – MK  | `POST/DELETE /curriculums/{id}/courses` | `curriculums.update` |
| Prasyarat MK | `POST/DELETE /curriculums/{id}/prerequisites` | `curriculums.update` |
| Jenis Tagihan | `/bill-types` | resource tersendiri |

Endpoint tambahan khusus Mahasiswa (`GET /students/{id}` memuat detail untuk admin):
- biodata lengkap, status, prodi, angkatan
- riwayat KRS (`/students/{id}/study-plans`)
- riwayat pembayaran (`/students/{id}/tuition-bills`)
- riwayat nilai (`/students/{id}/grades`)
- riwayat presensi ringkas (`/students/{id}/attendance-summary`)

Endpoint tambahan khusus Dosen (`GET /lecturers/{id}`):
- biodata, jabatan, status, mata kuliah yang diampu (`/lecturers/{id}/course-classes`), jadwal mengajar (`/lecturers/{id}/schedules`).

### 3.4 Kelas Kuliah & Jadwal

| Method | Endpoint | Catatan |
|---|---|---|
| GET | `/course-classes?semester_id&course_id&lecturer_id` | scope sesuai role (dosen hanya kelas diampu, dsb.) |
| POST | `/course-classes` (course_id, name, coordinator_lecturer_id, capacity, status, lecturers[]) | `course_classes.create` |
| PATCH/DELETE | `/course-classes/{id}` | 422 bila sudah ada peserta tak boleh hapus |
| GET | `/course-classes/{id}/students` | daftar mahasiswa yang mengambil kelas |
| GET | `/schedules?class_room_id&lecturer_id&day&semester_id` | `schedules.read` |
| POST | `/schedules` (course_class_id, day, start_time, end_time, class_room_id) | `schedules.create` — 422 bila bentrok ruang/dosen |
| PATCH/DELETE | `/schedules/{id}` | cek bentrok juga saat update | 
| GET | `/schedules/me` | jadwal sendiri (dosen → mengajar; mahasiswa → kelas diambilnya) |
| GET | `/schedules/room/{classRoomId}` | jadwal mingguan ruang |

### 3.5 KRS

| Method | Endpoint | Deskripsi / Permission |
|---|---|---|
| GET | `/study-plans?semester_id&status&student_id` | admin semua; dosen yang diwali; mahasiswa dirinya |
| POST | `/study-plans` | membuat atau mengembalikan draft KRS semester aktif (mahasiswa) |
| POST | `/study-plans/{id}/courses` | tambah mata kuliah: validasi periode, prasyarat, batas SKS, bentrok jadwal, kapasitas |
| DELETE | `/study-plans/{id}/courses/{courseClassId}` | hapus item (mahasiswa, hanya status draft) |
| POST | `/study-plans/{id}/submit` | mahasiswa — status → pending |
| POST | `/study-plans/{id}/approve` | dosen wali / admin — status → approved; catat `approved_at` |
| POST | `/study-plans/{id}/reject` / `/revision` | dosen wali / admin |
| GET | `/study-plans/{id}/detail` | list item + total SKS + status validasi tiap item |
| GET | `/study-plans/{id}/pdf` | cetak KRS PDF (DomPDF) |
| GET | `/study-plans/history` | riwayat KRS per mahasiswa tiap semester |

### 3.6 Presensi

| Method | Endpoint | Permission |
|---|---|---|
| GET | `/attendances?course_class_id` | read kelas yang diset (dosen miliknya) |
| POST | `/attendances` (course_class_id, meeting_no, date, topic) | `attendances.create` |
| POST | `/attendances/{id}/details` (massupsert: `[ {student_id, status H/I/S/A, note} ]`) | `attendances.create` — hanya sesi status `terbuka` |
| POST | `/attendances/{id}/close` | tutup pertemuan (dosen) |
| GET | `/students/me/attendance-summary` | mahasiswa: presensi dirinya semua kelas |
| GET | `/attendances/report?course_class_id&semester_id` | rekap kehadiran per kelas/siswa |

Pembatasan: dosen hanya bisa menginput pada kelas yang diampu (Policy); admin bebas akses seluruh kelas; mahasiswa hanya read dirinya.

### 3.7 Nilai, KHS, Transkrip

| Method | Endpoint | Deskripsi |
|---|---|---|
| GET/POST | `/course-classes/{id}/grade-components` | kelola komponen + bobot (total harus 100) |
| GET | `/course-classes/{id}/grades` | daftar mahasiswa + skor komponen |
| POST | `/course-classes/{id}/grades` | simpan/upsert skor komponen (massal) |
| POST | `/course-classes/{id}/grades/publish` | publish: hitung `final_score` + huruf + bobot; sesudah ini terkunci |
| PATCH | `/grades/{id}` | edit nilai sebelum publish |
| POST | `/grades/{id}/correct` | koreksi pasca-lulus (reason wajib) — admin; `grade_logs` tercatat |
| GET | `/students/me/grades` | nilai mahasiswa (published saja) |
| GET | `/students/me/khs?semester_id` | KHS satu semester: mata kuliah, nilai, SKS, IPS, IPK |
| GET | `/students/{id}/khs/pdf` | cetak KHS PDF |
| GET | `/students/me/transcript` | transkrip semua semester + total SKS + IPK |
| GET | `/students/{id}/transcript/pdf` | cetak transkrip PDF |

### 3.8 Pembayaran

| Method | Endpoint | Permission |
|---|---|---|
| GET | `/tuition-bills?student_id&status&semester_id` | mahasiswa: dirinya; keuangan/admin: semua |
| POST | `/tuition-bills` | buat tagihan (per mahasiswa atau batch per prodi/angkatan) |
| PATCH | `/tuition-bills/{id}` | edit nominal/due date (belum ada pembayaran verified) |
| POST | `/payments` | pembayaran + upload bukti (multipart image/pdf max 2 MB) |
| POST | `/payments/{id}/verify` | validasi keuangan (verify/reject + note) |
| GET | `/payments?status=pending` | antrian verifikasi keuangan |
| GET | `/students/me/payments` | riwayat pembayaran mahasiswa |
| GET | `/reports/payments?semester_id` | laporan pembayaran & validasi (lihat §3.10) |

### 3.9 Pengumuman

| Method | Endpoint | Permission |
|---|---|---|
| GET | `/announcements?search&is_published` | admin akademik untuk kelola; umum untuk baca published |
| POST | `/announcements` (title, content, targets[], is_published, published_at) | `announcements.create` |
| PATCH | `/announcements/{id}` | `announcements.update` |
| DELETE | `/announcements/{id}` | `announcements.delete` |
| GET | `/announcements/me` | pengumuman relevan untuk mahasiswa (prodi/angkatan/semester) |
| GET | `/announcements/latest?limit=5` | widget dashboard |

### 3.10 Laporan & Export

Endpoint: `/reports/{name}` dengan query `?study_program_id&entry_year&academic_year_id&semester_id&format=pdf|xlsx`.
Implementasi ekspor di `Services/Reports/*` (DomPDF untuk `format=pdf`; Maatwebsite Excel untuk `xlsx`).

| Endpoint | Isi |
|---|---|
| `/reports/students` | data mahasiswa aktif / nonaktif (filter prodi, angkatan, status) |
| `/reports/lecturers` | data dosen |
| `/reports/krs` | rekap KRS per semester (status approve dsb.) |
| `/reports/grades` | distribusi nilai per mata kuliah/kelas |
| `/reports/gpa` | IPK/IPS per prodi/angkatan |
| `/reports/attendance` | rekap presensi per kelas/semester/mahasiswa |
| `/reports/payments` | pembayaran dan validasi per semester |

### 3.11 Pengaturan Sistem

| Method | Endpoint | Permission |
|---|---|---|
| GET | `/settings` | `settings.read` |
| PATCH | `/settings` | `settings.update` — update partial (nama app, tahun akademik aktif, batas SKS default, skala grade, batas periode KRS, dsb.) |

---

## 4. Middleware & Pemetaan Permission

- `auth:sanctum` pada seluruh endpoint kecuali `/auth/login`.
- Middleware granular `permission:<name>` (Spatie) pada tiap route CRUD.
- Akses "baca data diri sendiri" dijaga Policy (bukan hanya permission): mahasiswa hanya melihat data miliknya, dosen hanya kelas/tanggung jawabnya.
- Middleware custom `EnsureUserIsActive` — menolak user nonaktif.
- `throttle:5,1` khusus login.
