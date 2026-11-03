"use client";
import IconifyIcon from "@/components/wrappers/IconifyIcon";
import { useLayoutContext } from "@/context/useLayoutContext";

const ThemeModeToggle = () => {
  const { theme, changeTheme } = useLayoutContext();
  const isDark = theme === "dark";
  return (
    <div className="topbar-item d-none d-sm-flex">
      <button
        className="topbar-link"
        onClick={() => changeTheme(isDark ? "light" : "dark")}
        id="light-dark-mode"
        type="button"
      >
        {theme == "dark" ? (
          <IconifyIcon
            icon="ri:sun-line"
            width={22}
            height={22}
            className="dark-mode-icon fs-22"
          />
        ) : (
          <IconifyIcon
            width={22}
            height={22}
            icon="ri:moon-line"
            className="light-mode-icon fs-22"
          />
        )}
      </button>
    </div>
  );
};

export default ThemeModeToggle;
