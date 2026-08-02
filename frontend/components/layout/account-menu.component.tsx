"use client";

import { useCallback, useRef, useState } from "react";
import Link from "next/link";
import { useRouter } from "next/navigation";
import { ChevronDown, LogOut, UserRound } from "lucide-react";

import { useAuth } from "@/lib/auth/context";
import { useClickOutside } from "@/lib/hooks/use-click-outside";
import { cn } from "@/lib/utils/cn";

export function AccountMenu() {
  const { user, logout } = useAuth();
  const router = useRouter();
  const [isOpen, setIsOpen] = useState(false);
  const containerRef = useRef<HTMLDivElement>(null);

  useClickOutside(containerRef, useCallback(() => setIsOpen(false), []));

  if (!user) return null;

  const initial = user.name.charAt(0).toUpperCase();
  const subtitle = user.role === "admin" && user.business ? user.business.name : user.email;

  async function handleLogout() {
    setIsOpen(false);
    await logout();
    router.push("/login");
  }

  return (
    <div ref={containerRef} className="relative">
      <button
        type="button"
        onClick={() => setIsOpen((open) => !open)}
        aria-label="Minha conta"
        aria-expanded={isOpen}
        className="flex cursor-pointer items-center gap-2 rounded-full py-1 pl-1 pr-2.5 text-sm font-medium text-foreground transition-colors hover:bg-accent"
      >
        <span className="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-primary text-xs font-semibold text-primary-foreground">
          {initial}
        </span>
        <span className="hidden max-w-28 truncate lg:inline">{user.name}</span>
        <ChevronDown className={cn("h-3.5 w-3.5 shrink-0 text-muted-foreground transition-transform", isOpen && "rotate-180")} />
      </button>

      {isOpen && (
        <div className="shadow-elevated-lg absolute right-0 top-11 z-20 w-64 rounded-xl border border-border bg-card">
          <div className="flex items-center gap-3 px-4 py-3">
            <span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-primary text-sm font-semibold text-primary-foreground">
              {initial}
            </span>
            <div className="flex min-w-0 flex-col">
              <span className="truncate text-sm font-medium text-foreground">{user.name}</span>
              <span className="truncate text-xs text-muted-foreground">{subtitle}</span>
            </div>
          </div>

          <div className="border-t border-border p-1.5">
            <Link
              href="/perfil"
              onClick={() => setIsOpen(false)}
              className="flex w-full items-center gap-2.5 rounded-lg px-2.5 py-2 text-sm text-muted-foreground transition-colors hover:bg-accent hover:text-accent-foreground"
            >
              <UserRound className="h-4 w-4" />
              Meu perfil
            </Link>
            <button
              type="button"
              onClick={handleLogout}
              className="flex w-full cursor-pointer items-center gap-2.5 rounded-lg px-2.5 py-2 text-sm text-muted-foreground transition-colors hover:bg-destructive/10 hover:text-destructive"
            >
              <LogOut className="h-4 w-4" />
              Sair
            </button>
          </div>
        </div>
      )}
    </div>
  );
}
