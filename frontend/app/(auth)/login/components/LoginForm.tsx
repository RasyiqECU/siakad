"use client";
import PasswordFormInput from "@/components/form/PasswordFormInput";
import TextFormInput from "@/components/form/TextFormInput";
import { login } from "@/services/api/auth";
import { saveSession } from "@/lib/auth";
import { useNotificationContext } from "@/context/useNotificationContext";
import { useRouter } from "next/navigation";
import { useState } from "react";
import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import { z } from "zod";
import { Button } from "react-bootstrap";
import type { AxiosError } from "axios";
import type { ApiResponse } from "@/types/api";

const loginSchema = z.object({
  email: z
    .string()
    .min(1, "Email wajib diisi")
    .email("Format email tidak valid"),
  password: z.string().min(1, "Password wajib diisi"),
});

type LoginFormFields = z.infer<typeof loginSchema>;

const LoginForm = () => {
  const { loading, submitLogin, control, serverError } = useLoginForm();

  return (
    <>
      {serverError && (
        <div className="alert alert-danger" role="alert">
          {serverError}
        </div>
      )}
      <form onSubmit={submitLogin} className="text-start mb-3">
        <div className="mb-3">
          <TextFormInput
            control={control}
            name="email"
            label="Email"
            containerClassName="form-group mb-2"
            placeholder="Masukkan email Anda"
          />
        </div>

        <div className="mb-3">
          <PasswordFormInput
            control={control}
            name="password"
            label="Password"
            containerClassName="form-group"
            placeholder="Masukkan password Anda"
          />
        </div>

        <div className="d-grid">
          <Button
            variant="primary"
            className="fw-semibold"
            type="submit"
            disabled={loading}
          >
            {loading ? "Memproses..." : "Masuk"}
          </Button>
        </div>
      </form>
    </>
  );
};

function useLoginForm() {
  const router = useRouter();
  const { showNotification } = useNotificationContext();
  const [loading, setLoading] = useState(false);
  const [serverError, setServerError] = useState<string | null>(null);

  const { control, handleSubmit } = useForm<LoginFormFields>({
    resolver: zodResolver(loginSchema),
    defaultValues: {
      email: "",
      password: "",
    },
  });

  const submitLogin = handleSubmit(async (values: LoginFormFields) => {
    setLoading(true);
    setServerError(null);
    try {
      const response = await login(values.email, values.password);
      saveSession(response.token, response.user);
      showNotification({
        message: "Berhasil masuk. Mengalihkan...",
        variant: "success",
      });
      router.push("/dashboard");
    } catch (err: unknown) {
      const axiosErr = err as AxiosError<ApiResponse<null>>;
      const msg =
        axiosErr.response?.data?.errors?.email?.[0] ??
        axiosErr.response?.data?.message ??
        null;
      setServerError(
        msg ?? "Tidak dapat menghubungi server. Pastikan API berjalan.",
      );
    } finally {
      setLoading(false);
    }
  });

  return { loading, submitLogin, control, serverError };
}

export default LoginForm;
