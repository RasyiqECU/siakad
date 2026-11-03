"use client";
import IconifyIcon from "@/components/wrappers/IconifyIcon";
import { useLayoutContext } from "@/context/useLayoutContext";

const HoverMenuToggle = () => {
  const {
    mainMenu: { size },
    changeMainMenuSize,
  } = useLayoutContext();

  const handleHoverMenu = () => {
    if (size === "sm-hover-active") changeMainMenuSize("sm-hover");
    else changeMainMenuSize("sm-hover-active");
  };

  return (
    <>
      <button onClick={handleHoverMenu} className="button-sm-hover">
        {size == "sm-hover" ? (
          <IconifyIcon
            width={20}
            height={20}
            icon="ri:circle-line"
            className="align-middle sm-hover"
          />
        ) : (
          <IconifyIcon
            width={20}
            height={20}
            icon="ri:record-circle-line"
            className="align-middle sm-hover-active"
          />
        )}
      </button>
    </>
  );
};

export default HoverMenuToggle;
