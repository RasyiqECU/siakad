"use client";
import LogoBox from "@/components/LogoBox";
import IconifyIcon from "@/components/wrappers/IconifyIcon";
import SimplebarReactClient from "@/components/wrappers/SimplebarReactClient";
import React, { Suspense } from "react";
import AppMenu from "./components/AppMenu";
import FallbackLoading from "@/components/FallbackLoading";
import HoverMenuToggle from "./components/HoverMenuToggle";
import { getMenuItems } from "@/helpers/menu";
import { filterMenuByPermission } from "@/components/layout/menuGating";
import { useQueryClient } from "@tanstack/react-query";
import { logout } from "@/services/api/auth";
import { clearAuth } from "@/lib/auth";
import { useAuth } from "@/hooks/useAuth";
import { useRouter } from "next/navigation";
import {
  Dropdown,
  DropdownDivider,
  DropdownHeader,
  DropdownItem,
  DropdownMenu,
  DropdownToggle,
} from "react-bootstrap";
import { useLayoutContext } from "@/context/useLayoutContext";
import useViewPort from "@/hooks/useViewPort";

const ROLE_LABEL: Record<string, string> = {
  super_admin: "Super Admin",
  admin_akademik: "Admin Akademik",
  dosen: "Dosen",
  mahasiswa: "Mahasiswa",
  keuangan: "Bagian Keuangan",
  pimpinan: "Pimpinan",
};

const LeftSideBar = () => {
  const router = useRouter();
  const queryClient = useQueryClient();
  const { user } = useAuth();
  const { mainMenu, changeMainMenuSize } = useLayoutContext();

  // Menu difilter sesuai permission dari /auth/me (super_admin melihat semuanya).
  const menuItems = filterMenuByPermission(
    getMenuItems(),
    user ? { permissions: user.permissions, roles: user.roles } : null,
  );

  const changeMenu = () => {
    if (mainMenu.size == "default") {
      changeMainMenuSize("condensed");
    } else if (mainMenu.size == "full") {
      changeMainMenuSize("default");
    }
  };

  const handleLogout = async () => {
    try {
      await logout();
    } catch {
      // token mungkin sudah tidak valid — sesi tetap dibersihkan
    } finally {
      clearAuth();
      queryClient.clear();
      router.replace("/login");
    }
  };

  return (
    <div className="sidenav-menu">
      <LogoBox />
      <HoverMenuToggle />

      <button onClick={changeMenu} className="sidenav-toggle-button">
        <IconifyIcon icon="ri:menu-5-line" className="fs-20" />
      </button>

      <SimplebarReactClient data-simplebar>
        <div className="sidenav-user">
          <Dropdown className="dropdown-center text-center">
            <DropdownToggle
              as="a"
              className="topbar-link text-reset drop-arrow-none px-2"
              data-bs-toggle="dropdown"
              type="button"
              aria-haspopup="false"
              aria-expanded="false"
            >
              <span className="d-flex align-items-center justify-content-center rounded-circle bg-primary fs-16 fw-semibold text-white" style={{ width: 46, height: 46 }}>
                {(user?.name ?? "?").charAt(0).toUpperCase()}
              </span>
              <span className="d-flex gap-1 sidenav-user-name my-2">
                <span>
                  <span className="mb-0 fw-semibold lh-base fs-15">
                    {user?.name ?? "Memuat..."}
                  </span>
                  <p className="my-0 fs-13 text-muted">
                    {(user?.roles ?? [])
                      .map((r) => ROLE_LABEL[r] ?? r)
                      .join(", ") || "—"}
                  </p>
                </span>
                <IconifyIcon
                  icon="ri:arrow-down-s-line"
                  className="d-block sidenav-user-arrow align-middle"
                />
              </span>
            </DropdownToggle>
            <DropdownMenu className="dropdown-menu-end">
              <DropdownHeader className="noti-title">
                <h6 className="text-overflow m-0">Selamat datang!</h6>
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
                onClick={() => void handleLogout()}
                role="button"
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

        <Suspense fallback={<FallbackLoading />}>
          <AppMenu menuItems={menuItems} />
        </Suspense>

        <div className="clearfix" />
      </SimplebarReactClient>
    </div>
  );
};

export default LeftSideBar;
