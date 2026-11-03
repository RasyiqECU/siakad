"use client";
import {
  createContext,
  use,
  useCallback,
  useEffect,
  useMemo,
  useRef,
  useState,
} from "react";
import { useLocalStorage } from "usehooks-ts";
import {
  LayoutModeType,
  LayoutOffcanvasStatesType,
  LayoutOrientationType,
  LayoutState,
  LayoutType,
  MainMenuType,
  OffcanvasControlType,
  ThemeType,
  TopBarType,
} from "@/types/layout";
import { ChildrenType } from "@/types/component-props";
import { toggleDocumentAttribute } from "@/utils/layout";

const INIT_STATE: LayoutState = {
  theme: "light",
  orientation: "vertical",
  mainMenu: {
    size: "default",
    color: "light",
    isMobileMenuOpen: false,
  },
  topBar: {
    color: "light",
  },
  title: "welcome",
  mode: "fluid",
};

const ThemeContext = createContext<LayoutType | undefined>(undefined);

const useLayoutContext = () => {
  const context = use(ThemeContext);
  if (!context) {
    throw new Error("useLayoutContext can only be used within LayoutProvider");
  }
  return context;
};

const LayoutProvider = ({ children }: ChildrenType) => {
  const [settings, setSettings] = useLocalStorage<LayoutState>(
    "__SIMORA_LAYOUT_CONFIG__",
    INIT_STATE,
  );

  const [offcanvasStates, setOffcanvasStates] =
    useState<LayoutOffcanvasStatesType>({
      showThemeCustomizer: false,
      showHorizontalMenu: false,
      showBackdrop: false,
    });

  const prevOrientation = useRef<LayoutOrientationType>(settings.orientation);

  // update settings
  const updateSettings = useCallback(
    (_newSettings: Partial<LayoutState>) => {
      setSettings((prevSettings) => ({
        ...prevSettings,
        ..._newSettings,
        mainMenu: {
          ...prevSettings.mainMenu,
          ...(_newSettings.mainMenu || {}),
        },
        topBar: {
          ...prevSettings.topBar,
          ...(_newSettings.topBar || {}),
        },
      }));
    },
    [setSettings],
  );

  const changeTheme = useCallback(
    (nTheme: ThemeType) => {
      updateSettings({ theme: nTheme });
    },
    [updateSettings],
  );

  const changeLayoutOrientation = useCallback(
    (nOrientation: LayoutOrientationType) => {
      prevOrientation.current = settings.orientation;
      updateSettings({ orientation: nOrientation });
    },
    [updateSettings, settings.orientation],
  );

  const changeMainMenuSize = useCallback(
    (nSize: MainMenuType["size"]) => {
      updateSettings({ mainMenu: { size: nSize } } as Partial<LayoutState>);
    },
    [updateSettings],
  );

  const changeMainMenuColor = useCallback(
    (nColor: MainMenuType["color"]) => {
      updateSettings({ mainMenu: { color: nColor } } as Partial<LayoutState>);
    },
    [updateSettings],
  );

  const changeTopColor = useCallback(
    (nColor: TopBarType["color"]) => {
      updateSettings({ topBar: { color: nColor } });
    },
    [updateSettings],
  );

  const changeLayoutMode = useCallback(
    (nMode: LayoutModeType) => {
      updateSettings({ mode: nMode });
    },
    [updateSettings],
  );

  const changeTitle = useCallback(
    (newTitle: string) => updateSettings({ title: newTitle }),
    [updateSettings],
  );

  const toggleMobileMenu = useCallback(
    (nValue: MainMenuType["isMobileMenuOpen"]) => {
      updateSettings({
        mainMenu: { isMobileMenuOpen: nValue },
      } as Partial<LayoutState>);
    },
    [updateSettings],
  );

  const toggleThemeCustomizer: OffcanvasControlType["toggle"] =
    useCallback(() => {
      setOffcanvasStates((prev) => ({
        ...prev,
        showThemeCustomizer: !prev.showThemeCustomizer,
      }));
    }, []);

  const toggleHorizontalMenu: OffcanvasControlType["toggle"] = useCallback(
    () => {
      setOffcanvasStates((prev) => ({
        ...prev,
        showHorizontalMenu: !prev.showHorizontalMenu,
      }));
    },
    [],
  );

  const themeCustomizer: LayoutType["themeCustomizer"] = {
    open: offcanvasStates.showThemeCustomizer,
    toggle: toggleThemeCustomizer,
  };

  const horizontalMenu: LayoutType["horizontalMenu"] = {
    open: offcanvasStates.showHorizontalMenu,
    toggle: toggleHorizontalMenu,
  };

  // toggle backdrop
  const toggleBackdrop = useCallback(() => {
    const htmlTag = document.getElementsByTagName("html")[0];
    if (offcanvasStates.showBackdrop)
      htmlTag.classList.remove("sidebar-enable");
    else htmlTag.classList.add("sidebar-enable");
    setOffcanvasStates({
      ...offcanvasStates,
      showBackdrop: !offcanvasStates.showBackdrop,
    });
  }, [offcanvasStates.showBackdrop]);

  useEffect(() => {
    toggleDocumentAttribute("data-bs-theme", settings.theme);
    toggleDocumentAttribute("data-topbar-color", settings.topBar.color);
    toggleDocumentAttribute("data-menu-color", settings.mainMenu.color);
    toggleDocumentAttribute("data-sidenav-size", settings.mainMenu.size);
    toggleDocumentAttribute("data-layout-mode", settings.mode);
    toggleDocumentAttribute(
      "data-layout",
      settings.orientation === "vertical" ? "" : "topnav",
    );
    return () => {
      toggleDocumentAttribute("data-bs-theme", settings.theme, true);
      toggleDocumentAttribute("data-topbar-color", settings.topBar.color, true);
      toggleDocumentAttribute(
        "data-menu-color",
        settings.mainMenu.color,
        true,
      );
      toggleDocumentAttribute("data-sidenav-size", settings.mainMenu.size, true);
      toggleDocumentAttribute("data-layout-mode", settings.mode, true);
      toggleDocumentAttribute(
        "data-layout",
        settings.orientation === "vertical" ? "" : "topnav",
        true,
      );
    };
  }, [settings]);

  const resetSettings = useCallback(
    () => updateSettings(INIT_STATE),
    [updateSettings],
  );

  return (
    <ThemeContext.Provider
      value={useMemo(
        () => ({
          ...settings,
          settings,
          prevOrientation: prevOrientation.current,
          horizontalMenu,
          themeCustomizer,
          changeTheme,
          changeLayoutOrientation,
          changeMainMenuSize,
          changeMainMenuColor,
          changeTopColor,
          changeLayoutMode,
          changeTitle,
          toggleMobileMenu,
          toggleBackdrop,
          resetSettings,
        }),
        [settings, offcanvasStates],
      )}
    >
      {children}
      {offcanvasStates.showBackdrop && (
        <>
          <div
            className="offcanvas-backdrop fade show"
            onClick={toggleBackdrop}
          ></div>
        </>
      )}
    </ThemeContext.Provider>
  );
};
export { LayoutProvider, useLayoutContext };
