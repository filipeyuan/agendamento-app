"use client";

import { useState } from "react";
import Link from "next/link";
import { usePathname, useRouter } from "next/navigation";
import { LogOut, Menu, X } from "lucide-react";

import { Logo } from "@/components/layout/logo.component";
import { ThemeToggle } from "@/components/layout/theme-toggle.component";
import { Button, buttonVariants } from "@/components/ui/button";
import { useAuth } from "@/lib/auth/context";
import { cn } from "@/lib/utils/cn";

function NavLink({ href, children }: { href: string; children: React.ReactNode }) {
  const pathname = usePathname();
  const isActive = pathname === href;

  return (
    <Link
      href={href}
      className={cn(
        "rounded-md px-3 py-1.5 text-sm font-medium text-muted-foreground transition-colors hover:bg-accent hover:text-accent-foreground",
        isActive && "bg-accent text-foreground"
      )}
    >
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
      <NavLink href="/servicos">Serviços</NavLink>

      {!isLoading && user?.role === "client" && (
        <>
          <NavLink href="/agendar">Agendar</NavLink>
          <NavLink href="/assistente">Assistente (IA)</NavLink>
          <NavLink href="/meus-agendamentos">Meus agendamentos</NavLink>
        </>
      )}

      {!isLoading && user?.role === "admin" && (
        <>
          <NavLink href="/admin/dashboard">Dashboard</NavLink>
          <NavLink href="/admin/servicos">Serviços (admin)</NavLink>
          <NavLink href="/admin/agendamentos">Agendamentos (admin)</NavLink>
          <NavLink href="/admin/horarios">Horários (admin)</NavLink>
        </>
      )}

      {!isLoading && !user && <NavLink href="/login">Entrar</NavLink>}
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

          <div className="ml-2 flex items-center gap-2 border-l border-border pl-3">
            <ThemeToggle />

            {!isLoading && user && (
              <Button variant="outline" size="sm" onClick={handleLogout}>
                <LogOut className="h-4 w-4" />
                Sair
              </Button>
            )}

            {!isLoading && !user && (
              <Link href="/cadastro" className={buttonVariants({ size: "sm" })}>
                Cadastrar
              </Link>
            )}
          </div>
        </nav>

        <div className="flex items-center gap-2 md:hidden">
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
