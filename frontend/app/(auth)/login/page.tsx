import logoDark from "@/assets/images/logo-dark.png";
import logo from "@/assets/images/logo.png";
import Image from "next/image";
import { Card, Col, Row } from "react-bootstrap";
import LoginForm from "./components/LoginForm";
import { currentYear } from "@/context/constants";

const LoginPage = () => {
  return (
    <div className="auth-bg d-flex min-vh-100">
      <Row className="g-0 justify-content-center w-100 m-xxl-5 px-xxl-4 m-3">
        <Col xxl={3} lg={5} md={6}>
          <a href="/dashboard" className="auth-brand d-flex justify-content-center mb-2">
            <Image
              src={logoDark}
              alt="SIMORA"
              height={26}
              className="logo-dark"
            />
            <Image
              src={logo}
              alt="SIMORA"
              height={26}
              className="logo-light"
            />
          </a>
          <p className="fw-semibold mb-4 text-center text-muted fs-15">
            Sistem Informasi Akademik
          </p>
          <Card className="overflow-hidden text-center p-xxl-4 p-3 mb-0">
            <h4 className="fw-semibold mb-3 fs-18">Masuk ke akun Anda</h4>
            <LoginForm />
            <div className="text-start">
              <p className="text-muted fs-13 mb-0">
                Akun demo: mahasiswa@simora.test / password — lihat README untuk
                akun per-role lainnya.
              </p>
            </div>
          </Card>
          <p className="mt-4 text-center mb-0">
            {currentYear} SIMORA — Sistem Informasi Akademik
          </p>
        </Col>
      </Row>
    </div>
  );
};

export default LoginPage;
