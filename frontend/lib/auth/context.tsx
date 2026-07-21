"use client";

import { createContext, useContext, type ReactNode } from "react";
import useSWR from "swr";

import * as authApi from "@/lib/api/auth";
import { clearToken, getToken, setToken } from "@/lib/auth/token";
import type { User } from "@/lib/types/users";

interface AuthContextValue {
  user: User | null;
  isLoading: boolean;
  login: (email: string, password: string) => Promise<User>;
  register: (payload: {
    name: string;
    email: string;
    password: string;
    password_confirmation: string;
    phone?: string;
  }) => Promise<User>;
  logout: () => Promise<void>;
}

const AuthContext = createContext<AuthContextValue | null>(null);

async function fetchCurrentUser(): Promise<User | null> {
  if (!getToken()) return null;

  try {
    return await authApi.me();
  } catch {
    clearToken();
    return null;
  }
}

export function AuthProvider({ children }: { children: ReactNode }) {
  const { data: user, isLoading, mutate } = useSWR("auth-user", fetchCurrentUser, {
    revalidateOnFocus: false,
  });

  async function login(email: string, password: string) {
    const { user, token } = await authApi.login({ email, password });
    setToken(token);
    await mutate(user, false);
    return user;
  }

  async function register(payload: {
    name: string;
    email: string;
    password: string;
    password_confirmation: string;
    phone?: string;
  }) {
    const { user, token } = await authApi.register(payload);
    setToken(token);
    await mutate(user, false);
    return user;
  }

  async function logout() {
    try {
      await authApi.logout();
    } finally {
      clearToken();
      await mutate(null, false);
    }
  }

  return (
    <AuthContext value={{ user: user ?? null, isLoading, login, register, logout }}>
      {children}
    </AuthContext>
  );
}

export function useAuth() {
  const context = useContext(AuthContext);
  if (!context) {
    throw new Error("useAuth precisa ser usado dentro de um AuthProvider");
  }
  return context;
}
