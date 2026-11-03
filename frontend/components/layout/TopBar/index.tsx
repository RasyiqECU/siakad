"use client";
import LogoBox from "@/components/LogoBox";
import IconifyIcon from "@/components/wrappers/IconifyIcon";
import { usePathname } from "next/navigation";
import { useEffect, useRef, useState } from "react";
import LeftSideBarToggle from "./components/LeftSideBarToggle";
import ProfileDropdown from "./components/ProfileDropdown";
import ThemeModeToggle from "./components/ThemeModeToggle";

const TopBar = () => {
  const navbarRef = useRef<HTMLDivElement | null>(null);

  useEffect(() => {
    window.addEventListener("scroll", () => {
      if (navbarRef.current)
        navbarRef.current.classList.toggle(
          "topbar-active",
          window.scrollY > 100,
        );
    });
  }, []);

  const pathname = usePathname();

  const [title, setTitle] = useState<string>("SIMORA");

  useEffect(() => {
    setTitle(document.title.split("|")[0]);
  }, [pathname]);

  return (
    <header ref={navbarRef} className="app-topbar" id="header">
      <div className="page-container topbar-menu">
        <div className="d-flex align-items-center gap-2">
          <LogoBox />
          <LeftSideBarToggle />
          <div className="topbar-item d-none d-md-flex px-2">
            <div>
              <h4 className="page-title fs-20 fw-semibold mb-0">{title}</h4>
            </div>
          </div>
        </div>
        <div className="d-flex align-items-center gap-2">
          <ThemeModeToggle />
          <ProfileDropdown />
        </div>
      </div>
    </header>
  );
};

export default TopBar;
