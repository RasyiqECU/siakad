import { redirect } from "next/navigation";

/** Halaman root: arahkan ke dashboard (guard sesi ditangani proxy.ts + admin layout). */
export default function HomePage() {
  redirect("/dashboard");
}
