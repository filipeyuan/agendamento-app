import { apiRequest } from "@/lib/api/client";
import type { User } from "@/lib/types/users";

interface AuthResponse {
  user: User;
  token: string;
}

export function register(payload: {
  name: string;
  email: string;
  password: string;
  password_confirmation: string;
  phone?: string;
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

export function me() {
  return apiRequest<User>("/me");
}
