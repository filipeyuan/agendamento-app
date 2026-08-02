"use client";

import Link from "next/link";

import { buttonVariants } from "@/components/ui/button";
import { useAuth } from "@/lib/auth/context";
import { cn } from "@/lib/utils/cn";

const primaryClass = cn(
  buttonVariants({ size: "lg" }),
  "bg-[var(--hero-primary)] text-[var(--hero-primary-foreground)] shadow-none hover:opacity-90"
);
const outlineClass = cn(
  buttonVariants({ variant: "outline", size: "lg" }),
  "border-[var(--hero-border)] bg-transparent text-[var(--hero-fg)] hover:bg-white/10"
);

export function HeroActions() {
  const { user, isLoading } = useAuth();

  if (isLoading) {
    return null;
  }

  if (!user) {
    return (
      <>
        <Link href="/servicos" className={primaryClass}>
          Ver serviços
        </Link>
        <Link href="/cadastro" className={outlineClass}>
          Criar conta
        </Link>
      </>
    );
  }

  if (user.role === "admin") {
    return (
      <>
        <Link href="/admin/agendamentos" className={primaryClass}>
          Ir para o painel
        </Link>
        <Link href="/admin/servicos" className={outlineClass}>
          Gerenciar serviços
        </Link>
      </>
    );
  }

  return (
    <>
      <Link href="/agendar" className={primaryClass}>
        Agendar horário
      </Link>
      <Link href="/meus-agendamentos" className={outlineClass}>
        Meus agendamentos
      </Link>
    </>
  );
}
