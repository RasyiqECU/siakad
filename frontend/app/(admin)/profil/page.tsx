"use client";
import PageHeader from "@/components/shared/PageHeader";
import PasswordFormInput from "@/components/form/PasswordFormInput";
import TextFormInput from "@/components/form/TextFormInput";
import IconifyIcon from "@/components/wrappers/IconifyIcon";
import { useAuth } from "@/hooks/useAuth";
import { fetchProfile, updateProfile } from "@/services/api/profile";
import { getApiErrorMessage } from "@/lib/apiError";
import { updateCachedUser } from "@/lib/auth";
import { useQuery, useQueryClient } from "@tanstack/react-query";
import { zodResolver } from "@hookform/resolvers/zod";
import { useForm } from "react-hook-form";
import { z } from "zod";
import { useEffect, useState } from "react";
import { toast } from "react-toastify";
import { Alert, Badge, Button, Card, Col, Row, Tab, Tabs } from "react-bootstrap";

/**
 * Profil Saya (Phase 2): identitas dari /auth/me + detail /profile,
 * ubah profil (nama/telepon) & ganti password (validasi password lama).
 */

const ROLE_LABEL: Record<string, string> = {
  super_admin: "Super Admin",
  admin_akademik: "Admin Akademik",
  dosen: "Dosen",
  mahasiswa: "Mahasiswa",
  keuangan: "Bagian Keuangan",
  pimpinan: "Pimpinan",
};

const profileSchema = z.object({
  name: z.string().min(1, "Nama wajib diisi"),
  phone: z.string().optional(),
});
type ProfileFields = z.infer<typeof profileSchema>;

const passwordSchema = z
  .object({
    current_password: z.string().min(1, "Password saat ini wajib diisi"),
    password: z.string().min(8, "Password baru minimal 8 karakter"),
    password_confirmation: z
      .string()
      .min(1, "Konfirmasi password wajib diisi"),
  })
  .refine((v) => v.password === v.password_confirmation, {
    message: "Konfirmasi password tidak sama",
    path: ["password_confirmation"],
  });
type PasswordFields = z.infer<typeof passwordSchema>;

const ProfilPage = () => {
  const { user } = useAuth();
  const queryClient = useQueryClient();

  // Detail /profile (nama terbaru, dsb.) — fallback ke user /auth/me saat loading.
  const profileQuery = useQuery({
    queryKey: ["profile"],
    queryFn: fetchProfile,
    enabled: Boolean(user),
  });

  const profile = profileQuery.data;
  const displayName = profile?.name ?? user?.name ?? "—";
  const displayEmail = profile?.email ?? user?.email ?? "—";
  const displayRoles = profile?.roles ?? user?.roles ?? [];
  const displayAvatar = profile?.avatar ?? user?.avatar ?? null;

  // ---- form: ubah profil ----
  const {
    control: profileControl,
    handleSubmit: handleProfileSubmit,
    reset: resetProfile,
  } = useForm<ProfileFields>({
    resolver: zodResolver(profileSchema),
    defaultValues: { name: "", phone: "" },
  });

  useEffect(() => {
    if (profile) {
      resetProfile({ name: profile.name, phone: "" });
    }
  }, [profile, resetProfile]);

  const [profileError, setProfileError] = useState<string | null>(null);
  const [savingProfile, setSavingProfile] = useState(false);

  const onProfileSubmit = handleProfileSubmit(async (values) => {
    setSavingProfile(true);
    setProfileError(null);
    try {
      await updateProfile({ name: values.name, phone: values.phone || undefined });
      toast.success("Profil berhasil diperbarui.");
      // segarkan identitas ter-cache & server state (sidebar/TopBar ikut berubah)
      updateCachedUser({ name: values.name });
      void queryClient.invalidateQueries({ queryKey: ["profile"] });
      void queryClient.invalidateQueries({ queryKey: ["auth", "me"] });
    } catch (error) {
      setProfileError(getApiErrorMessage(error));
      toast.error(getApiErrorMessage(error));
    } finally {
      setSavingProfile(false);
    }
  });

  // ---- form: ganti password ----
  const {
    control: passwordControl,
    handleSubmit: handlePasswordSubmit,
    reset: resetPassword,
  } = useForm<PasswordFields>({
    resolver: zodResolver(passwordSchema),
    defaultValues: {
      current_password: "",
      password: "",
      password_confirmation: "",
    },
  });

  const [passwordError, setPasswordError] = useState<string | null>(null);
  const [savingPassword, setSavingPassword] = useState(false);

  const onPasswordSubmit = handlePasswordSubmit(async (values) => {
    setSavingPassword(true);
    setPasswordError(null);
    try {
      await updateProfile({
        name: displayName,
        current_password: values.current_password,
        password: values.password,
        password_confirmation: values.password_confirmation,
      });
      toast.success("Password berhasil diubah.");
      resetPassword();
      void queryClient.invalidateQueries({ queryKey: ["auth", "me"] });
    } catch (error) {
      // 422 dari backend: password saat ini salah / validasi gagal
      setPasswordError(getApiErrorMessage(error));
      toast.error(getApiErrorMessage(error));
    } finally {
      setSavingPassword(false);
    }
  });

  return (
    <>
      <PageHeader
        title="Profil Saya"
        subtitle="Informasi akun, perubahan data diri, dan keamanan password."
      />

      <Row className="g-3">
        <Col md={4}>
          <Card className="text-center p-4 h-100">
            {displayAvatar ? (
              <img
                src={displayAvatar}
                alt={displayName}
                className="rounded-circle border border-2"
                width={88}
                height={88}
                style={{ objectFit: "cover" }}
              />
            ) : (
              <div
                className="avatar-xl mx-auto bg-primary rounded-circle d-flex align-items-center justify-content-center text-white fs-24 mb-3"
                style={{ width: 88, height: 88 }}
              >
                {displayName !== "—" ? displayName.charAt(0).toUpperCase() : "?"}
              </div>
            )}
            <h4 className="fw-semibold mb-1">{displayName}</h4>
            <p className="text-muted fs-13 mb-2 d-flex align-items-center justify-content-center gap-1">
              <IconifyIcon icon="ri:mail-line" /> {displayEmail}
            </p>
            <div className="d-flex flex-wrap gap-1 justify-content-center">
              {displayRoles.map((role) => (
                <Badge
                  key={role}
                  className="bg-success-subtle text-success"
                >
                  {ROLE_LABEL[role] ?? role}
                </Badge>
              ))}
            </div>
          </Card>
        </Col>

        <Col md={8}>
          <Card>
            <Card.Body>
              <Tabs defaultActiveKey="profil" className="mb-3">
                <Tab eventKey="profil" title="Ubah Profil">
                  {profileError && (
                    <Alert variant="danger" className="py-2 fs-13">
                      {profileError}
                    </Alert>
                  )}
                  <form onSubmit={onProfileSubmit}>
                    <div className="mb-3">
                      <TextFormInput
                        control={profileControl}
                        name="name"
                        label="Nama Lengkap"
                        placeholder="Masukkan nama lengkap"
                      />
                    </div>
                    <div className="mb-3">
                      <TextFormInput
                        control={profileControl}
                        name="phone"
                        label="No. HP (opsional)"
                        placeholder="08xxxxxxxxxx"
                      />
                    </div>
                    <Button
                      variant="primary"
                      type="submit"
                      disabled={savingProfile}
                    >
                      {savingProfile ? "Menyimpan..." : "Simpan Perubahan"}
                    </Button>
                  </form>
                </Tab>

                <Tab eventKey="password" title="Ganti Password">
                  {passwordError && (
                    <Alert variant="danger" className="py-2 fs-13">
                      {passwordError}
                    </Alert>
                  )}
                  <form onSubmit={onPasswordSubmit}>
                    <div className="mb-3">
                      <PasswordFormInput
                        control={passwordControl}
                        name="current_password"
                        label="Password Saat Ini"
                        placeholder="Masukkan password saat ini"
                      />
                    </div>
                    <div className="mb-3">
                      <PasswordFormInput
                        control={passwordControl}
                        name="password"
                        label="Password Baru"
                        placeholder="Minimal 8 karakter"
                      />
                    </div>
                    <div className="mb-3">
                      <PasswordFormInput
                        control={passwordControl}
                        name="password_confirmation"
                        label="Konfirmasi Password Baru"
                        placeholder="Ulangi password baru"
                      />
                    </div>
                    <Button
                      variant="primary"
                      type="submit"
                      disabled={savingPassword}
                    >
                      {savingPassword ? "Menyimpan..." : "Ganti Password"}
                    </Button>
                  </form>
                </Tab>
              </Tabs>
            </Card.Body>
          </Card>
        </Col>
      </Row>
    </>
  );
};

export default ProfilPage;
