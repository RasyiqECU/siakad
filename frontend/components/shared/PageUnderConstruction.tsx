"use client";
import IconifyIcon from "@/components/wrappers/IconifyIcon";
import { Card } from "react-bootstrap";

const MODULE_LABEL: Record<string, string> = {
  mahasiswa: "Manajemen Mahasiswa",
  dosen: "Manajemen Dosen",
  "kelas-kuliah": "Kelas Kuliah",
  jadwal: "Jadwal Kuliah",
  krs: "Kartu Rencana Studi (KRS)",
  presensi: "Presensi",
  nilai: "Nilai",
  khs: "Kartu Hasil Studi (KHS)",
  transkrip: "Transkrip Nilai",
  pembayaran: "Pembayaran",
  tagihan: "Tagihan",
  pengumuman: "Pengumuman",
  laporan: "Laporan",
  pengguna: "Manajemen Pengguna",
  pengaturan: "Pengaturan Sistem",
  profil: "Profil Saya",
  master: "Data Master",
};

const TITLE_MAP: Record<string, string> = {
  fakultas: "Fakultas",
  jurusan: "Jurusan",
  prodi: "Program Studi",
  "tahun-akademik": "Tahun Akademik",
  semester: "Semester",
  "status-akademik": "Status Akademik",
  gedung: "Gedung",
  "ruang-kelas": "Ruang Kelas",
};

/** Placeholder modul — jujur menandakan modul belum diimplementasikan pada fase tertentu. */
const PageUnderConstruction = ({
  moduleName,
  group,
}: {
  moduleName: string;
  group?: string;
}) => {
  const label =
    group === "master"
      ? `Data Master — ${TITLE_MAP[moduleName] ?? moduleName}`
      : (MODULE_LABEL[moduleName] ?? moduleName);

  return (
    <Card className="p-5 text-center">
      <div className="avatar-xl mx-auto bg-warning-subtle rounded-circle d-flex align-items-center justify-content-center mb-3">
        <IconifyIcon icon="ri:hammer-line" className="fs-28 text-warning" />
      </div>
      <h4 className="fw-semibold mb-1">{label}</h4>
      <p className="text-muted mb-0 fs-14">
        Modul ini akan diimplementasikan mengikuti tahapan pengerjaan
        (lihat docs/PROJECT_PLAN.md).
      </p>
    </Card>
  );
};

export default PageUnderConstruction;
