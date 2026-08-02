"use client";

import { useEffect, useState } from "react";
import Link from "next/link";
import { usePathname, useRouter } from "next/navigation";
import { AnimatePresence, motion, useReducedMotion } from "motion/react";
import {
  CalendarCheck2,
  CalendarPlus,
  CalendarRange,
  LayoutDashboard,
  LogIn,
  LogOut,
  Menu,
  Sparkles,
  Store,
  X,
  type LucideIcon,
} from "lucide-react";

import { AccountMenu } from "@/components/layout/account-menu.component";
import { Logo } from "@/components/layout/logo.component";
import { NotificationBell } from "@/components/layout/notification-bell.component";
import { ThemeToggle } from "@/components/layout/theme-toggle.component";
import { Wordmark } from "@/components/layout/wordmark.component";
import { Button, buttonVariants } from "@/components/ui/button";
import { Spinner } from "@/components/ui/spinner";
import { useAuth } from "@/lib/auth/context";
import { cn } from "@/lib/utils/cn";

function NavLink({
  href,
  icon: Icon,
  onNavigate,
  children,
}: {
  href: string;
  icon: LucideIcon;
  onNavigate?: () => void;
  children: React.ReactNode;
}) {
  const pathname = usePathname();
  const isActive = pathname === href.split("?")[0];

  return (
    <Link
      href={href}
      onClick={onNavigate}
      className={cn(
        "flex items-center gap-1.5 rounded-full px-3.5 py-1.5 text-sm font-medium text-muted-foreground transition-colors hover:bg-accent hover:text-accent-foreground",
        isActive && "bg-primary/10 text-primary hover:bg-primary/10 hover:text-primary"
      )}
    >
      <Icon className="h-4 w-4 md:hidden" />
      {children}
    </Link>
  );
}

function MobileMenuHeader({ onClose }: { onClose: () => void }) {
  const { user } = useAuth();
  const initial = user?.name.charAt(0).toUpperCase();

  return (
    <div className="relative bg-primary px-5 pb-5 pt-4 text-primary-foreground">
      <button
        type="button"
        onClick={onClose}
        aria-label="Fechar menu"
        className="absolute right-3 top-3 flex h-8 w-8 items-center justify-center rounded-full text-primary-foreground/80 hover:bg-primary-foreground/10 hover:text-primary-foreground"
      >
        <X className="h-4 w-4" />
      </button>

      <Link href="/" onClick={onClose} className="flex items-center gap-2 font-heading text-lg font-semibold">
        <Logo />
        <Wordmark />
      </Link>

      {user ? (
        <div className="mt-4 flex items-center gap-3">
          <span className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-primary-foreground/15 text-sm font-semibold">
            {initial}
          </span>
          <div className="flex min-w-0 flex-col">
            <span className="truncate text-sm font-medium">Olá, {user.name.split(" ")[0]}!</span>
            <span className="truncate text-xs text-primary-foreground/70">
              {user.role === "admin" && user.business ? user.business.name : user.email}
            </span>
          </div>
        </div>
      ) : (
        <p className="mt-4 text-sm text-primary-foreground/80">
          Encontre um negócio e agende seu horário em poucos cliques.
        </p>
      )}
    </div>
  );
}

export function Navbar() {
  const { user, isLoading, logout } = useAuth();
  const router = useRouter();
  const pathname = usePathname();
  const [isMenuOpen, setIsMenuOpen] = useState(false);
  const [lastPathname, setLastPathname] = useState(pathname);
  const [isScrolled, setIsScrolled] = useState(false);
  const shouldReduceMotion = useReducedMotion();
  const hasDarkHero = pathname === "/";
  const isTransparent = hasDarkHero && !isScrolled;

  if (pathname !== lastPathname) {
    setLastPathname(pathname);
    setIsMenuOpen(false);
  }

  useEffect(() => {
    if (!hasDarkHero) return;

    function handleScroll() {
      setIsScrolled(window.scrollY > 240);
    }

    handleScroll();
    window.addEventListener("scroll", handleScroll, { passive: true });
    return () => window.removeEventListener("scroll", handleScroll);
  }, [hasDarkHero]);

  useEffect(() => {
    if (!isMenuOpen) return;

    function handleKeyDown(event: KeyboardEvent) {
      if (event.key === "Escape") setIsMenuOpen(false);
    }

    document.addEventListener("keydown", handleKeyDown);
    return () => document.removeEventListener("keydown", handleKeyDown);
  }, [isMenuOpen]);

  async function handleLogout() {
    setIsMenuOpen(false);
    await logout();
    router.push("/login");
  }

  function handleLogoClick(event: React.MouseEvent) {
    if (pathname === "/") {
      event.preventDefault();
      window.scrollTo({ top: 0, behavior: shouldReduceMotion ? "auto" : "smooth" });
    }
  }

  function navLinks(onNavigate?: () => void) {
    if (isLoading) {
      return (
        <span className="flex items-center px-3 py-1.5 text-muted-foreground">
          <Spinner className="h-4 w-4" />
        </span>
      );
    }

    if (!user) {
      return (
        <>
          <NavLink href="/servicos" icon={Store} onNavigate={onNavigate}>
            Serviços
          </NavLink>
          <NavLink href="/login" icon={LogIn} onNavigate={onNavigate}>
            Entrar
          </NavLink>
        </>
      );
    }

    const quickLinks =
      user.role === "admin"
        ? [
            { href: "/admin/dashboard", label: "Dashboard", icon: LayoutDashboard },
            { href: "/admin/agendamentos", label: "Agendamentos", icon: CalendarRange },
            {
              href: user.business ? `/assistente?business=${user.business.slug}` : "/assistente",
              label: "Assistente",
              icon: Sparkles,
            },
          ]
        : [
            { href: "/agendar", label: "Agendar", icon: CalendarPlus },
            { href: "/meus-agendamentos", label: "Meus agendamentos", icon: CalendarCheck2 },
            { href: "/assistente", label: "Assistente", icon: Sparkles },
          ];

    return quickLinks.map((item, index) => (
      <Link
        key={item.href}
        href={item.href}
        onClick={onNavigate}
        className={cn(
          "flex items-center gap-1.5 rounded-full px-3.5 py-1.5 text-sm font-medium transition-colors",
          index === 0
            ? "bg-primary text-primary-foreground shadow-[0_4px_14px_-4px_var(--primary)] hover:opacity-90"
            : "border border-border text-muted-foreground hover:bg-accent hover:text-accent-foreground"
        )}
      >
        <item.icon className="h-4 w-4" />
        {item.label}
      </Link>
    ));
  }

  return (
    <>
      <header
        className={cn(
          "sticky top-0 z-10 transition-colors duration-300",
          isTransparent
            ? "nav-on-dark border-b border-transparent bg-transparent"
            : "border-b border-border bg-background/80 backdrop-blur"
        )}
      >
        <div className="mx-auto flex h-16 max-w-6xl items-center gap-4 px-4 sm:px-6">
        <Link
          href="/"
          onClick={handleLogoClick}
          className="flex shrink-0 items-center gap-2.5 font-heading text-2xl font-bold text-foreground"
        >
          <Logo className="h-9 w-9" iconClassName="h-5 w-5" />
          <Wordmark />
        </Link>

        <nav className="hidden items-center gap-2 pl-6 md:flex">
          {navLinks()}
        </nav>

        <div className="ml-auto hidden items-center gap-2 md:flex">
          <NotificationBell />
          <ThemeToggle />

          {isLoading && <Spinner className="ml-1 h-4 w-4 text-muted-foreground" />}

          {!isLoading && user && <AccountMenu />}

          {!isLoading && !user && (
            <Link href="/cadastro" className={cn(buttonVariants({ size: "sm" }), "rounded-full")}>
              Cadastrar
            </Link>
          )}
        </div>

          <div className="ml-auto flex items-center gap-1 md:hidden">
            <NotificationBell />
            <ThemeToggle />
            <button
              type="button"
              onClick={() => setIsMenuOpen(true)}
              aria-label="Abrir menu"
              className="flex h-9 w-9 items-center justify-center rounded-full text-foreground hover:bg-accent"
            >
              <Menu className="h-5 w-5" />
            </button>
          </div>
        </div>
      </header>

      <AnimatePresence>
        {isMenuOpen && (
          <motion.div
            key="mobile-nav-backdrop"
            className="fixed inset-0 z-40 bg-black/50 md:hidden"
            initial={{ opacity: 0 }}
            animate={{ opacity: 1 }}
            exit={{ opacity: 0 }}
            transition={{ duration: shouldReduceMotion ? 0 : 0.2 }}
            onClick={() => setIsMenuOpen(false)}
            aria-hidden
          />
        )}
      </AnimatePresence>

      <AnimatePresence>
        {isMenuOpen && (
          <motion.div
            key="mobile-nav-panel"
            className="shadow-elevated-lg fixed inset-y-0 left-0 z-40 flex w-72 max-w-[85vw] flex-col overflow-hidden border-r border-border bg-background md:hidden"
            initial={{ x: "-100%" }}
            animate={{ x: 0 }}
            exit={{ x: "-100%" }}
            transition={shouldReduceMotion ? { duration: 0 } : { type: "spring", damping: 28, stiffness: 320 }}
          >
            <MobileMenuHeader onClose={() => setIsMenuOpen(false)} />

            <nav className="flex flex-1 flex-col gap-1.5 overflow-y-auto px-3 py-4">
              {navLinks(() => setIsMenuOpen(false))}
            </nav>

            <div className="border-t border-border p-3">
              {!isLoading && user && (
                <Button variant="outline" size="sm" onClick={handleLogout} className="w-full justify-center">
                  <LogOut className="h-4 w-4" />
                  Sair
                </Button>
              )}

              {!isLoading && !user && (
                <Link
                  href="/cadastro"
                  onClick={() => setIsMenuOpen(false)}
                  className={cn(buttonVariants({ size: "sm" }), "w-full justify-center rounded-full")}
                >
                  Cadastrar
                </Link>
              )}
            </div>
          </motion.div>
        )}
      </AnimatePresence>
    </>
  );
}
