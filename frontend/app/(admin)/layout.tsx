"use client";
import Footer from "@/components/layout/Footer";
import LeftSideBar from "@/components/layout/LeftSideBar";
import TopBar from "@/components/layout/TopBar";
import { useAuth } from "@/hooks/useAuth";
import { ChildrenType } from "@/types/component-props";
import { useRouter } from "next/navigation";
import { useEffect } from "react";
import { Spinner } from "react-bootstrap";

/**
 * Layout area ter-autentikasi (tema Adminto):
 * TopBar + LeftSideBar + page-content, dengan guard sesi dari /auth/me.
 * (Halaman ini hanya frame; otorisasi sebenarnya tetap diverifikasi backend.)
 */
const AdminLayout = ({ children }: ChildrenType) => {
  const router = useRouter();
  const { isAuthenticated, isLoading } = useAuth();

  useEffect(() => {
    if (!isLoading && !isAuthenticated) {
      router.replace("/login");
    }
  }, [isAuthenticated, isLoading, router]);

  if (isLoading) {
    return (
      <div className="d-flex flex-column align-items-center justify-content-center min-vh-100 gap-3">
        <Spinner animation="border" variant="primary" />
        <span className="text-muted">Memuat sesi...</span>
      </div>
    );
  }

  if (!isAuthenticated) return null;

  return (
    <div className="wrapper">
      <TopBar />
      <LeftSideBar />
      <div className="page-content">
        <div className="page-container">{children}</div>
        <Footer />
      </div>
    </div>
  );
};

export default AdminLayout;
