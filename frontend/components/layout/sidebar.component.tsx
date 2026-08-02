"use client";

import Link from "next/link";
import { usePathname, useRouter } from "next/navigation";
import {
  CalendarCheck2,
  CalendarPlus,
  CalendarRange,
  Clock,
  LayoutDashboard,
  LogOut,
  Settings2,
  Sparkles,
  Store,
  X,
  type LucideIcon,
} from "lucide-react";

import { Logo } from "@/components/layout/logo.component";
import { ThemeToggle } from "@/components/layout/theme-toggle.component";
import { Wordmark } from "@/components/layout/wordmark.component";
import { Button } from "@/components/ui/button";
import { useAuth } from "@/lib/auth/context";
import { cn } from "@/lib/utils/cn";

function SidebarLink({
  href,
  icon: Icon,
  children,
  onNavigate,
}: {
  href: string;
  icon: LucideIcon;
  children: React.ReactNode;
  onNavigate?: () => void;
}) {
  const pathname = usePathname();
  const isActive = pathname === href.split("?")[0];

  return (
    <Link
      href={href}
      onClick={onNavigate}
      className={cn(
        "flex items-center gap-2.5 rounded-md px-3 py-2 text-sm font-medium text-muted-foreground transition-colors hover:bg-accent hover:text-accent-foreground",
        isActive && "bg-accent text-foreground"
      )}
    >
      <Icon className="h-4 w-4 shrink-0" />
      {children}
    </Link>
  );
}

function SidebarLinks({ onNavigate }: { onNavigate?: () => void }) {
  const { user, isLoading } = useAuth();

  if (isLoading || !user) return null;

  return (
    <nav className="flex flex-col gap-1">
      {user.role === "client" && (
        <>
          <SidebarLink href="/agendar" icon={CalendarPlus} onNavigate={onNavigate}>
            Agendar
          </SidebarLink>
          <SidebarLink href="/assistente" icon={Sparkles} onNavigate={onNavigate}>
            Assistente
          </SidebarLink>
          <SidebarLink href="/meus-agendamentos" icon={CalendarCheck2} onNavigate={onNavigate}>
            Meus agendamentos
          </SidebarLink>
          <SidebarLink href="/servicos" icon={Store} onNavigate={onNavigate}>
            Ver negócios
          </SidebarLink>
        </>
      )}

      {user.role === "admin" && (
        <>
          <SidebarLink href="/admin/dashboard" icon={LayoutDashboard} onNavigate={onNavigate}>
            Dashboard
          </SidebarLink>
          <SidebarLink href="/admin/servicos" icon={Settings2} onNavigate={onNavigate}>
            Serviços
          </SidebarLink>
          <SidebarLink href="/admin/agendamentos" icon={CalendarRange} onNavigate={onNavigate}>
            Agendamentos
          </SidebarLink>
          <SidebarLink href="/admin/horarios" icon={Clock} onNavigate={onNavigate}>
            Horários
          </SidebarLink>
          <SidebarLink
            href={user.business ? `/assistente?business=${user.business.slug}` : "/assistente"}
            icon={Sparkles}
            onNavigate={onNavigate}
          >
            Assistente
          </SidebarLink>
        </>
      )}
    </nav>
  );
}

function UserBlock() {
  const { user } = useAuth();
  if (!user) return null;

  const initial = user.name.charAt(0).toUpperCase();
  const subtitle = user.role === "admin" && user.business ? user.business.name : user.email;

  return (
    <div className="flex items-center gap-3 px-2">
      <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-primary text-sm font-semibold text-primary-foreground">
        {initial}
      </div>
      <div className="flex min-w-0 flex-col">
        <span className="truncate text-sm font-medium text-foreground">{user.name}</span>
        <span className="truncate text-xs text-muted-foreground">{subtitle}</span>
      </div>
    </div>
  );
}

function SidebarContent({ onNavigate }: { onNavigate?: () => void }) {
  const { logout } = useAuth();
  const router = useRouter();

  async function handleLogout() {
    await logout();
    router.push("/login");
  }

  return (
    <div className="flex h-full flex-col gap-6 p-4">
      <Link href="/" className="flex items-center gap-2 px-2 font-heading text-lg font-semibold text-foreground">
        <Logo />
        <Wordmark />
      </Link>

      <UserBlock />

      <div className="flex-1 overflow-y-auto">
        <SidebarLinks onNavigate={onNavigate} />
      </div>

      <div className="flex items-center justify-between border-t border-border pt-4">
        <ThemeToggle />
        <Button variant="outline" size="sm" onClick={handleLogout}>
          <LogOut className="h-4 w-4" />
          Sair
        </Button>
      </div>
    </div>
  );
}

export function Sidebar({ isOpen, onClose }: { isOpen: boolean; onClose: () => void }) {
  return (
    <>
      <aside className="hidden w-64 shrink-0 border-r border-border md:block">
        <div className="sticky top-0 h-screen">
          <SidebarContent />
        </div>
      </aside>

      {isOpen && (
        <div className="fixed inset-0 z-40 md:hidden">
          <div className="absolute inset-0 bg-black/50" onClick={onClose} aria-hidden />
          <div className="absolute inset-y-0 left-0 w-72 max-w-[80vw] border-r border-border bg-background shadow-xl">
            <button
              type="button"
              onClick={onClose}
              aria-label="Fechar menu"
              className="absolute right-3 top-3 flex h-8 w-8 items-center justify-center rounded-md text-muted-foreground hover:bg-accent"
            >
              <X className="h-4 w-4" />
            </button>
            <SidebarContent onNavigate={onClose} />
          </div>
        </div>
      )}
    </>
  );
}
