"use client";

import { createContext, useContext, useEffect, useState, type ReactNode } from "react";

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

export function AuthProvider({ children }: { children: ReactNode }) {
  const [user, setUser] = useState<User | null>(null);
  const [isLoading, setIsLoading] = useState(true);

  useEffect(() => {
    if (!getToken()) {
      setIsLoading(false);
      return;
    }

    authApi
      .me()
      .then(setUser)
      .catch(() => clearToken())
      .finally(() => setIsLoading(false));
  }, []);

  async function login(email: string, password: string) {
    const { user, token } = await authApi.login({ email, password });
    setToken(token);
    setUser(user);
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
    setUser(user);
    return user;
  }

  async function logout() {
    try {
      await authApi.logout();
    } finally {
      clearToken();
      setUser(null);
    }
  }

  return (
    <AuthContext value={{ user, isLoading, login, register, logout }}>
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
