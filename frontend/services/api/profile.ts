import api from "@/lib/api";
import type { ApiResponse } from "@/types/api";
import type { ProfileUser } from "@/types/user";

/**
 * Endpoint profil pengguna sendiri.
 * updateProfile mengirim multipart ketika avatar file diikutkan, selain itu JSON.
 */

export type ProfileUpdateInput = {
  name: string;
  phone?: string;
  avatar?: File | null;
  current_password?: string;
  password?: string;
  password_confirmation?: string;
};

export async function fetchProfile(): Promise<ProfileUser> {
  const res = await api.get<ApiResponse<ProfileUser>>("/profile");
  return res.data.data as ProfileUser;
}

export async function updateProfile(
  input: ProfileUpdateInput,
): Promise<ProfileUser> {
  const { avatar, ...fields } = input;

  if (avatar instanceof File) {
    const form = new FormData();
    form.append("name", fields.name);
    form.append("_method", "PATCH");
    if (fields.phone) form.append("phone", fields.phone);
    if (fields.current_password)
      form.append("current_password", fields.current_password);
    if (fields.password) form.append("password", fields.password);
    if (fields.password_confirmation)
      form.append("password_confirmation", fields.password_confirmation);
    form.append("avatar", avatar);
    const res = await api.post<ApiResponse<ProfileUser>>("/profile", form, {
      headers: { "Content-Type": "multipart/form-data" },
    });
    return res.data.data as ProfileUser;
  }

  const res = await api.patch<ApiResponse<ProfileUser>>("/profile", fields);
  return res.data.data as ProfileUser;
}
