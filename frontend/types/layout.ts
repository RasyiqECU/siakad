/**
 * SIMORA — konstanta aplikasi & tipe tata letak.
 * Menurunkan konvensi tema Adminto.
 */

// ===== Tipe layout (dari tema Adminto) =====

export type ThemeType = "light" | "dark";

export type MainMenuType = {
  size:
    | "default"
    | "compact"
    | "condensed"
    | "hidden"
    | "sm-hover"
    | "full"
    | "fullscreen"
    | "sm-hover-active";
  color: "light" | "dark" | "brand";
  isMobileMenuOpen: boolean;
};

export type LayoutOrientationType =
  | "vertical"
  | "horizontal"
  | "icon-view"
  | "two-column";

export type LayoutModeType = "fluid" | "detached";

export type TopBarType = {
  color: "light" | "dark" | "brand";
};

export type LayoutState = {
  theme: ThemeType;
  title: string;
  orientation: LayoutOrientationType;
  mainMenu: MainMenuType;
  topBar: TopBarType;
  mode: LayoutModeType;
};

export type LayoutOffcanvasStatesType = {
  showThemeCustomizer: boolean;
  showHorizontalMenu: boolean;
  showBackdrop: boolean;
};

export type OffcanvasControlType = {
  open: boolean;
  toggle: () => void;
};

export type LayoutType = LayoutState & {
  settings: LayoutState;
  changeTheme: (theme: ThemeType) => void;
  changeLayoutOrientation: (orientation: LayoutOrientationType) => void;
  changeMainMenuSize: (size: MainMenuType["size"]) => void;
  changeMainMenuColor: (color: MainMenuType["color"]) => void;
  changeTopColor: (color: TopBarType["color"]) => void;
  changeLayoutMode: (mode: LayoutModeType) => void;
  changeTitle: (title: string) => void;
  toggleMobileMenu: (
    isMobileMenuOpen: MainMenuType["isMobileMenuOpen"],
  ) => void;
  themeCustomizer: OffcanvasControlType;
  horizontalMenu: OffcanvasControlType;
  toggleBackdrop: () => void;
  resetSettings: () => void;
};

// ===== Tipe menu sidebar =====

export type MenuItemType = {
  key: string;
  label: string;
  isTitle?: boolean;
  icon?: string;
  url?: string;
  badge?: {
    variant: string;
    text: string;
    icon?: string;
  };
  badgeIcon?: string;
  parentKey?: string;
  target?: string;
  isDisabled?: boolean;
  permission?: string; // gating menu berdasarkan permission (Phase 2+)
  children?: MenuItemType[];
};

export type WrapperSettingsType = {
  settings: LayoutState;
};

export type SubMenus = {
  item: MenuItemType;
  linkClassName?: string;
  subMenuClassName?: string;
  tag?: string;
  activeMenuItems?: Array<string>;
  toggleMenu?: (item: MenuItemType, status: boolean) => void;
  className?: string;
  level: number;
};
