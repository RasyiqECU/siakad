import AppProvidersWrapper from "@/app/AppProvidersWrapper";
import { DEFAULT_PAGE_TITLE } from "@/context/constants";
import type { Metadata } from "next";
import { Outfit, Public_Sans } from "next/font/google";

import "@/assets/scss/app.scss";
import "./globals.css";

const publicSans = Public_Sans({
  weight: ["100", "200", "300", "400", "500", "600", "700", "800", "900"],
  display: "swap",
});

const outfit = Outfit({
  weight: ["400", "500", "600", "700", "800", "900"],
  display: "swap",
});

export const metadata: Metadata = {
  title: {
    template: "%s | SIMORA",
    default: DEFAULT_PAGE_TITLE,
  },
  description:
    "SIMORA — Sistem Informasi Akademik perguruan tinggi (KRS, presensi, nilai, KHS, transkrip, pembayaran).",
};

export default function RootLayout({
  children,
}: Readonly<{
  children: React.ReactNode;
}>) {
  return (
    <html lang="id">
      <body className={`${publicSans.className} ${outfit.className}`}>
        <AppProvidersWrapper>{children}</AppProvidersWrapper>
      </body>
    </html>
  );
}
