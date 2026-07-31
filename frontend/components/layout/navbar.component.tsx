"use client";

import { useState } from "react";
import Link from "next/link";
import { usePathname, useRouter } from "next/navigation";
import {
  CalendarCheck2,
  CalendarPlus,
  CalendarRange,
  Clock,
  LayoutDashboard,
  LogIn,
  LogOut,
  Menu,
  Settings2,
  Sparkles,
  Store,
  X,
  type LucideIcon,
} from "lucide-react";

import { Logo } from "@/components/layout/logo.component";
import { NotificationBell } from "@/components/layout/notification-bell.component";
import { ThemeToggle } from "@/components/layout/theme-toggle.component";
import { Button, buttonVariants } from "@/components/ui/button";
import { Spinner } from "@/components/ui/spinner";
import { useAuth } from "@/lib/auth/context";
import { cn } from "@/lib/utils/cn";

function NavLink({
  href,
  icon: Icon,
  children,
}: {
  href: string;
  icon: LucideIcon;
  children: React.ReactNode;
}) {
  const pathname = usePathname();
  const isActive = pathname === href.split("?")[0];

  return (
    <Link
      href={href}
      className={cn(
        "flex items-center gap-1.5 rounded-md px-3 py-1.5 text-sm font-medium text-muted-foreground transition-colors hover:bg-accent hover:text-accent-foreground",
        isActive && "bg-accent text-foreground"
      )}
    >
      <Icon className="h-4 w-4" />
      {children}
    </Link>
  );
}

export function Navbar() {
  const { user, isLoading, logout } = useAuth();
  const router = useRouter();
  const pathname = usePathname();
  const [isMenuOpen, setIsMenuOpen] = useState(false);
  const [lastPathname, setLastPathname] = useState(pathname);

  if (pathname !== lastPathname) {
    setLastPathname(pathname);
    setIsMenuOpen(false);
  }

  async function handleLogout() {
    await logout();
    router.push("/login");
  }

  const navLinks = (
    <>
      <NavLink href="/servicos" icon={Store}>
        Serviços
      </NavLink>

      {isLoading && (
        <span className="flex items-center px-3 py-1.5 text-muted-foreground">
          <Spinner className="h-4 w-4" />
        </span>
      )}

      {!isLoading && user?.role === "client" && (
        <>
          <NavLink href="/agendar" icon={CalendarPlus}>
            Agendar
          </NavLink>
          <NavLink href="/assistente" icon={Sparkles}>
            Assistente
          </NavLink>
          <NavLink href="/meus-agendamentos" icon={CalendarCheck2}>
            Meus agendamentos
          </NavLink>
        </>
      )}

      {!isLoading && user?.role === "admin" && (
        <>
          <div className="mx-1 hidden h-5 w-px bg-border md:block" aria-hidden />
          {user.business && (
            <span className="hidden items-center gap-1.5 px-2 text-sm font-medium text-muted-foreground md:flex">
              <Store className="h-4 w-4" />
              {user.business.name}
            </span>
          )}
          <NavLink href="/admin/dashboard" icon={LayoutDashboard}>
            Dashboard
          </NavLink>
          <NavLink href="/admin/servicos" icon={Settings2}>
            Serviços
          </NavLink>
          <NavLink href="/admin/agendamentos" icon={CalendarRange}>
            Agendamentos
          </NavLink>
          <NavLink href="/admin/horarios" icon={Clock}>
            Horários
          </NavLink>
          <NavLink
            href={user.business ? `/assistente?business=${user.business.slug}` : "/assistente"}
            icon={Sparkles}
          >
            Assistente
          </NavLink>
        </>
      )}

      {!isLoading && !user && (
        <NavLink href="/login" icon={LogIn}>
          Entrar
        </NavLink>
      )}
    </>
  );

  return (
    <header className="sticky top-0 z-10 border-b border-border bg-background/80 backdrop-blur">
      <div className="mx-auto flex h-16 max-w-5xl items-center justify-between px-4">
        <Link href="/" className="flex items-center gap-2 font-heading text-lg font-semibold text-foreground">
          <Logo />
          Zelo
        </Link>

        <nav className="hidden items-center gap-1 md:flex">
          {navLinks}

          <div className="ml-2 flex items-center gap-1 border-l border-border pl-3">
            <NotificationBell />
            <ThemeToggle />

            {isLoading && <Spinner className="ml-1 h-4 w-4 text-muted-foreground" />}

            {!isLoading && user && (
              <Button variant="outline" size="sm" onClick={handleLogout} className="ml-1">
                <LogOut className="h-4 w-4" />
                Sair
              </Button>
            )}

            {!isLoading && !user && (
              <Link href="/cadastro" className={cn(buttonVariants({ size: "sm" }), "ml-1")}>
                Cadastrar
              </Link>
            )}
          </div>
        </nav>

        <div className="flex items-center gap-1 md:hidden">
          <NotificationBell />
          <ThemeToggle />
          <button
            type="button"
            onClick={() => setIsMenuOpen((open) => !open)}
            aria-label={isMenuOpen ? "Fechar menu" : "Abrir menu"}
            className="flex h-9 w-9 items-center justify-center rounded-md text-foreground hover:bg-accent"
          >
            {isMenuOpen ? <X className="h-5 w-5" /> : <Menu className="h-5 w-5" />}
          </button>
        </div>
      </div>

      {isMenuOpen && (
        <nav className="flex flex-col gap-1 border-t border-border px-4 py-3 md:hidden">
          {navLinks}

          {!isLoading && user && (
            <Button variant="outline" size="sm" onClick={handleLogout} className="mt-2 justify-center">
              <LogOut className="h-4 w-4" />
              Sair
            </Button>
          )}

          {!isLoading && !user && (
            <Link href="/cadastro" className={cn(buttonVariants({ size: "sm" }), "mt-2 justify-center")}>
              Cadastrar
            </Link>
          )}
        </nav>
      )}
    </header>
  );
}
