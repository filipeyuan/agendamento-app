"use client";

import { useEffect, type ReactNode } from "react";
import { useRouter } from "next/navigation";

import { Spinner } from "@/components/ui/spinner";
import { useAuth } from "@/lib/auth/context";
import type { UserRole } from "@/lib/types/users";

export function RequireAuth({ children, role }: { children: ReactNode; role?: UserRole }) {
  const { user, isLoading } = useAuth();
  const router = useRouter();

  const isAuthorized = !!user && (!role || user.role === role);

  useEffect(() => {
    if (isLoading) return;
    if (!user) {
      router.replace("/login");
      return;
    }
    if (role && user.role !== role) {
      router.replace("/");
    }
  }, [isLoading, user, role, router]);

  if (isLoading || !isAuthorized) {
    return (
      <div className="flex flex-1 items-center justify-center py-24">
        <Spinner className="h-6 w-6 text-muted-foreground" />
      </div>
    );
  }

  return <>{children}</>;
}
