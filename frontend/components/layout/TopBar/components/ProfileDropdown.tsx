"use client";
import IconifyIcon from "@/components/wrappers/IconifyIcon";
import { useQueryClient } from "@tanstack/react-query";
import { useRouter } from "next/navigation";
import {
  Dropdown,
  DropdownDivider,
  DropdownHeader,
  DropdownItem,
  DropdownMenu,
  DropdownToggle,
} from "react-bootstrap";
import { useAuth } from "@/hooks/useAuth";
import { logout } from "@/services/api/auth";
import { clearAuth } from "@/lib/auth";

const ROLE_LABEL: Record<string, string> = {
  super_admin: "Super Admin",
  admin_akademik: "Admin Akademik",
  dosen: "Dosen",
  mahasiswa: "Mahasiswa",
  keuangan: "Bagian Keuangan",
  pimpinan: "Pimpinan",
};

const ProfileDropdown = () => {
  const { user } = useAuth();
  const router = useRouter();
  const queryClient = useQueryClient();

  const handleLogout = async () => {
    try {
      await logout();
    } catch {
      // token ketidakvalid; bersihkan sesi di sisi klien tetap diperlukan
    } finally {
      clearAuth();
      queryClient.clear();
      router.replace("/login");
    }
  };

  return (
    <div className="topbar-item nav-user">
      <Dropdown>
        <DropdownToggle
          className="topbar-link drop-arrow-none px-2"
          data-bs-toggle="dropdown"
          data-bs-offset="0,25"
          type="button"
          aria-haspopup="false"
          aria-expanded="false"
        >
          <span className="d-flex align-items-center justify-content-center rounded-circle bg-primary fs-12 fw-semibold text-white d-lg-flex" style={{ width: 32, height: 32 }}>
            {(user?.name ?? "?").charAt(0).toUpperCase()}
          </span>
          <span className="d-lg-flex flex-column gap-1 d-none">
            <h5 className="my-0">{user?.name ?? "Memuat..."}</h5>
            <span className="fs-12 text-muted">
              {(user?.roles ?? [])
                .map((r) => ROLE_LABEL[r] ?? r)
                .join(", ") || "—"}
            </span>
          </span>
          <IconifyIcon
            icon="ri:arrow-down-s-line"
            className="d-none d-lg-block align-middle ms-1"
          />
        </DropdownToggle>
        <DropdownMenu className="dropdown-menu-end">
          <DropdownHeader className="noti-title">
            <h6 className="text-overflow m-0">{user?.email ?? "-"}</h6>
          </DropdownHeader>
          <DropdownItem as="a" href="/profil">
            <IconifyIcon
              icon="ri:account-circle-line"
              className="me-1 fs-16 align-middle"
            />
            <span className="align-middle">Profil Saya</span>
          </DropdownItem>
          <DropdownItem as="a" href="/pengaturan">
            <IconifyIcon
              icon="ri:settings-2-line"
              className="me-1 fs-16 align-middle"
            />
            <span className="align-middle">Pengaturan</span>
          </DropdownItem>
          <DropdownDivider />
          <DropdownItem
            className="active fw-semibold text-danger"
            role="button"
            onClick={() => void handleLogout()}
          >
            <IconifyIcon
              icon="ri:logout-box-line"
              className="me-1 fs-16 align-middle"
            />
            <span className="align-middle">Keluar</span>
          </DropdownItem>
        </DropdownMenu>
      </Dropdown>
    </div>
  );
};

export default ProfileDropdown;
