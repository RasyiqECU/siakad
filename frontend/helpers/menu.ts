import { MenuItemType } from "@/types/layout";

/**
 * Struktur menu sidebar SIMORA.
 * - `permission` diisi untuk gating menu per-role (diterapkan di Phase 2/3).
 * - target modul yang belum diimplementasikan menampilkan halaman placeholder.
 */
const MENU_ITEMS: MenuItemType[] = [
  { key: "Utama", label: "Utama", isTitle: true },
  {
    key: "Dashboard",
    label: "Dashboard",
    icon: "ri:home-4-line",
    url: "/dashboard",
  },
  {
    key: "Profil",
    label: "Profil Saya",
    icon: "ri:user-3-line",
    url: "/profil",
  },

  {
    key: "Akademik",
    label: "Akademik",
    isTitle: true,
  },
  {
    key: "Mahasiswa",
    label: "Mahasiswa",
    icon: "ri:user-voice-line",
    url: "/mahasiswa",
    permission: "students.read",
  },
  {
    key: "Dosen",
    label: "Dosen",
    icon: "ri:user-star-line",
    url: "/dosen",
    permission: "lecturers.read",
  },
  {
    key: "KelasKuliah",
    label: "Kelas Kuliah",
    icon: "ri:door-open-line",
    url: "/kelas-kuliah",
  },
  { key: "Jadwal", label: "Jadwal Kuliah", icon: "ri:calendar-event-line", url: "/jadwal" },
  { key: "KRS", label: "KRS", icon: "ri:file-list-3-line", url: "/krs" },
  {
    key: "Presensi",
    label: "Presensi",
    icon: "ri:checkbox-multiple-line",
    url: "/presensi",
  },
  { key: "Nilai", label: "Nilai", icon: "ri:medal-line", url: "/nilai" },
  { key: "KHS", label: "KHS", icon: "ri:file-list-2-line", url: "/khs" },
  {
    key: "Transkrip",
    label: "Transkrip",
    icon: "ri:file-paper-2-line",
    url: "/transkrip",
  },

  {
    key: "DataMaster",
    label: "Data Master",
    isTitle: true,
  },
  {
    key: "MasterFakultas",
    label: "Fakultas",
    icon: "ri:building-2-line",
    url: "/master/fakultas",
    parentKey: "DataMaster",
    permission: "faculties.read",
  },
  {
    key: "MasterJurusan",
    label: "Jurusan",
    icon: "ri:school-line",
    url: "/master/jurusan",
    parentKey: "DataMaster",
    permission: "departments.read",
  },
  {
    key: "MasterProdi",
    label: "Program Studi",
    icon: "ri:book-open-line",
    url: "/master/prodi",
    parentKey: "DataMaster",
    permission: "study_programs.read",
  },
  {
    key: "MasterTahunAkademik",
    label: "Tahun Akademik",
    icon: "ri:calendar-line",
    url: "/master/tahun-akademik",
    parentKey: "DataMaster",
    permission: "academic_years.read",
  },
  {
    key: "MasterSemester",
    label: "Semester",
    icon: "ri:time-line",
    url: "/master/semester",
    parentKey: "DataMaster",
    permission: "semesters.read",
  },
  {
    key: "MasterStatusAkademik",
    label: "Status Akademik",
    icon: "ri:bookmark-2-line",
    url: "/master/status-akademik",
    parentKey: "DataMaster",
    permission: "academic_statuses.read",
  },
  {
    key: "MasterGedung",
    label: "Gedung",
    icon: "ri:building-line",
    url: "/master/gedung",
    parentKey: "DataMaster",
    permission: "buildings.read",
  },
  {
    key: "MasterRuang",
    label: "Ruang Kelas",
    icon: "ri:door-closed-line",
    url: "/master/ruang-kelas",
    parentKey: "DataMaster",
    permission: "class_rooms.read",
  },
  {
    key: "MasterMataKuliah",
    label: "Mata Kuliah",
    icon: "ri:book-2-line",
    url: "/mata-kuliah",
    parentKey: "DataMaster",
    permission: "courses.read",
  },
  {
    key: "MasterKurikulum",
    label: "Kurikulum",
    icon: "ri:file-list-3-line",
    url: "/kurikulum",
    parentKey: "DataMaster",
    permission: "curriculums.read",
  },

  {
    key: "Layanan",
    label: "Layanan",
    isTitle: true,
  },
  {
    key: "Pembayaran",
    label: "Pembayaran",
    icon: "ri:wallet-3-line",
    url: "/pembayaran",
  },
  {
    key: "Pengumuman",
    label: "Pengumuman",
    icon: "ri:notification-4-line",
    url: "/pengumuman",
  },
  {
    key: "Laporan",
    label: "Laporan",
    icon: "ri:file-chart-line",
    url: "/laporan",
  },

  {
    key: "Administrasi",
    label: "Administrasi",
    isTitle: true,
  },
  { key: "Pengguna", label: "Pengguna", icon: "ri:group-line", url: "/pengguna", permission: "users.read" },
  {
    key: "AuditLog",
    label: "Audit Log",
    icon: "ri:file-shield-2-line",
    url: "/audit-log",
    permission: "users.read",
  },
  {
    key: "Pengaturan",
    label: "Pengaturan",
    icon: "ri:settings-4-line",
    url: "/pengaturan",
  },
];

export const getMenuItems = (): MenuItemType[] => MENU_ITEMS;

export const findAllParent = (
  menuItems: MenuItemType[],
  menuItem: MenuItemType,
): string[] => {
  let parents: string[] = [];
  const parent = findMenuItem(menuItems, menuItem.parentKey);
  if (parent) {
    parents.push(parent.key);
    if (parent.parentKey) {
      parents = [...parents, ...findAllParent(menuItems, parent)];
    }
  }
  return parents;
};

export const getMenuItemFromURL = (
  items: MenuItemType | MenuItemType[],
  url: string,
): MenuItemType | undefined => {
  if (items instanceof Array) {
    for (const item of items) {
      const foundItem = getMenuItemFromURL(item, url);
      if (foundItem) {
        return foundItem;
      }
    }
  } else {
    if (items.url == url) return items;
    if (items.children != null) {
      for (const item of items.children) {
        if (item.url == url) return item;
      }
    }
  }
};

export const findMenuItem = (
  menuItems: MenuItemType[] | undefined,
  menuItemKey: MenuItemType["key"] | undefined,
): MenuItemType | null => {
  if (menuItems && menuItemKey) {
    for (const item of menuItems) {
      if (item.key === menuItemKey) {
        return item;
      }
      const found = findMenuItem(item.children, menuItemKey);
      if (found) return found;
    }
  }
  return null;
};
