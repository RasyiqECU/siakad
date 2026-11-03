"use client";
import { useAuth } from "@/hooks/useAuth";
import IconifyIcon from "@/components/wrappers/IconifyIcon";
import { Card, Row, Col } from "react-bootstrap";

/**
 * Dashboard Phase 1: identitas pengguna nyata dari /auth/me.
 * Statistik & grafik per-role akan menyusul saat endpoint /dashboard tersedia (Phase 6).
 */
const DashboardPage = () => {
  const { user } = useAuth();

  const MODULES: Array<{ name: string; icon: string; phase: string }> = [
    { name: "Data Master", icon: "ri:database-2-line", phase: "Phase 3" },
    { name: "Mahasiswa & Dosen", icon: "ri:user-voices-line", phase: "Phase 3" },
    { name: "Kurikulum & MK", icon: "ri:book-2-line", phase: "Phase 3" },
    { name: "KRS", icon: "ri:file-list-3-line", phase: "Phase 4" },
    { name: "Jadwal & Presensi", icon: "ri:calendar-check-line", phase: "Phase 4" },
    { name: "Nilai & KHS", icon: "ri:medal-line", phase: "Phase 4" },
    { name: "Pembayaran", icon: "ri:wallet-3-line", phase: "Phase 5" },
    { name: "Dashboard & Laporan", icon: "ri:bar-chart-2-line", phase: "Phase 6" },
  ];

  return (
    <>
      <Row className="g-3 mb-3">
        <Col>
          <Card className="text-center p-4">
            <div className="avatar-xl mx-auto bg-primary rounded-circle d-flex align-items-center justify-content-center text-white fs-24 mb-3">
              <IconifyIcon icon="ri:user-4-line" className="fs-28" />
            </div>
            <h4 className="fw-semibold mb-1">Selamat datang, {user?.name}!</h4>
            <p className="text-muted mb-0 fs-14">
              {user?.email} — role:{" "}
              <span className="badge bg-success-subtle text-success">
                {user?.roles?.join(", ") ?? "-"}
              </span>
            </p>
          </Card>
        </Col>
      </Row>

      <Row className="g-3">
        {MODULES.map((m) => (
          <Col md={6} xl={3} key={m.name}>
            <Card className="p-3 h-100">
              <div className="d-flex align-items-center gap-3">
                <div className="avatar-sm bg-body rounded-4 d-flex align-items-center justify-content-center flex-shrink-0">
                  <IconifyIcon icon={m.icon} className="fs-20 text-muted" />
                </div>
                <div>
                  <h5 className="my-0 fs-15 fw-semibold">{m.name}</h5>
                  <span className="fs-12 text-muted">
                    Dibangun pada {m.phase}
                  </span>
                </div>
              </div>
            </Card>
          </Col>
        ))}
      </Row>
    </>
  );
};

export default DashboardPage;
