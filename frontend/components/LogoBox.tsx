import Image from "next/image";
import React from "react";
import logoSm from "@/assets/images/logo-sm.png";
import logo from "@/assets/images/logo.png";
import logoDark from "@/assets/images/logo-dark.png";

const LogoBox = () => {
  return (
    <a href="/dashboard" className="logo">
      <span className="logo-light">
        <span className="logo-lg">
          <Image width={110} height={22} src={logo} alt="SIMORA" />
        </span>
        <span className="logo-sm">
          <Image width={28} height={28} src={logoSm} alt="SIMORA" />
        </span>
      </span>
      <span className="logo-dark">
        <span className="logo-lg">
          <Image width={110} height={22} src={logoDark} alt="SIMORA" />
        </span>
        <span className="logo-sm">
          <Image width={28} height={28} src={logoSm} alt="SIMORA" />
        </span>
      </span>
    </a>
  );
};

export default LogoBox;
