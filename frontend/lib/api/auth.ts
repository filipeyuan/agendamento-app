import { apiRequest } from "@/lib/api/client";
import type { User } from "@/lib/types/users";

interface AuthResponse {
  user: User;
  token: string;
}

interface ApiResource<T> {
  data: T;
}

export function register(payload: {
  name: string;
  email: string;
  password: string;
  password_confirmation: string;
  phone?: string;
  account_type: "client" | "business";
  business_name?: string;
}) {
  return apiRequest<AuthResponse>("/register", {
    method: "POST",
    body: JSON.stringify(payload),
  });
}

export function login(payload: { email: string; password: string }) {
  return apiRequest<AuthResponse>("/login", {
    method: "POST",
    body: JSON.stringify(payload),
  });
}

export function logout() {
  return apiRequest<void>("/logout", { method: "POST" });
}

export async function me() {
  const { data } = await apiRequest<ApiResource<User>>("/me");
  return data;
}

export async function updateProfile(payload: { name: string; email: string; phone?: string }) {
  const { data } = await apiRequest<ApiResource<User>>("/me", {
    method: "PUT",
    body: JSON.stringify(payload),
  });
  return data;
}

export function updatePassword(payload: {
  current_password: string;
  password: string;
  password_confirmation: string;
}) {
  return apiRequest<{ message: string }>("/me/password", {
    method: "PUT",
    body: JSON.stringify(payload),
  });
}
