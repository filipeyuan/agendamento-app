"use client";

import Link from "next/link";
import { usePathname, useRouter } from "next/navigation";

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
        "text-sm font-medium text-muted-foreground transition-colors hover:text-foreground",
        isActive && "text-foreground"
      )}
    >
      {children}
    </Link>
  );
}

export function Navbar() {
  const { user, isLoading, logout } = useAuth();
  const router = useRouter();

  async function handleLogout() {
    await logout();
    router.push("/login");
  }

  return (
    <header className="border-b border-border">
      <div className="mx-auto flex h-16 max-w-5xl items-center justify-between px-4">
        <Link href="/" className="font-semibold text-foreground">
          Agendamento App
        </Link>

        <nav className="flex items-center gap-6">
          <NavLink href="/servicos">Serviços</NavLink>

          {!isLoading && user?.role === "client" && (
            <>
              <NavLink href="/agendar">Agendar</NavLink>
              <NavLink href="/meus-agendamentos">Meus agendamentos</NavLink>
            </>
          )}

          {!isLoading && user?.role === "admin" && (
            <>
              <NavLink href="/admin/servicos">Serviços (admin)</NavLink>
              <NavLink href="/admin/agendamentos">Agendamentos (admin)</NavLink>
            </>
          )}

          {!isLoading && user && (
            <Button variant="outline" size="sm" onClick={handleLogout}>
              Sair
            </Button>
          )}

          {!isLoading && !user && (
            <>
              <NavLink href="/login">Entrar</NavLink>
              <Link href="/cadastro" className={buttonVariants({ size: "sm" })}>
                Cadastrar
              </Link>
            </>
          )}
        </nav>
      </div>
    </header>
  );
}
