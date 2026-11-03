import { NextResponse, type NextRequest } from "next/server";

/**
 * Guard sesi di sisi server (konvensi Next 16: proxy.js — pengganti middleware.js).
 * - Tanpa cookie token: semua halaman kecuali /login diarahkan ke /login.
 * - Dengan cookie token: akses /login diarahkan ke /dashboard.
 *
 * Bukti cepat, bukan otorisasi final — token tetap diverifikasi
 * oleh backend (auth:sanctum) dan layar admin memanggil /auth/me.
 */
const LOGIN_PATH = "/login";

export function proxy(request: NextRequest) {
  const { pathname, search } = request.nextUrl;
  const token = request.cookies.get("simora_token")?.value;

  if (!token && pathname !== LOGIN_PATH) {
    const loginUrl = new URL(LOGIN_PATH, request.url);
    loginUrl.searchParams.set("from", `${pathname}${search}`);
    return NextResponse.redirect(loginUrl);
  }

  if (token && pathname === LOGIN_PATH) {
    return NextResponse.redirect(new URL("/dashboard", request.url));
  }

  return NextResponse.next();
}

export const config = {
  matcher: [
    "/((?!_next/static|_next/image|favicon.ico|assets|api|sitemap.xml|robots.txt).*)",
  ],
};
