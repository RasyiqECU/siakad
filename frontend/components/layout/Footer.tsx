import { currentYear } from "@/context/constants";
import { Col, Row } from "react-bootstrap";

const Footer = () => {
  return (
    <footer className="footer">
      <div className="page-container">
        <Row>
          <Col md={6} className="text-center text-md-start">
            {currentYear} SIMORA — Sistem Informasi Akademik
          </Col>
          <Col md={6}>
            <div className="text-md-end footer-links d-none d-md-block">
              <span className="text-muted">Sistem Informasi Akademik</span>
            </div>
          </Col>
        </Row>
      </div>
    </footer>
  );
};

export default Footer;
