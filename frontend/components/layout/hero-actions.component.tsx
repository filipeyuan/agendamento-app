"use client";

import Link from "next/link";

import { buttonVariants } from "@/components/ui/button";
import { useAuth } from "@/lib/auth/context";
import { cn } from "@/lib/utils/cn";

export function HeroActions() {
  const { user, isLoading } = useAuth();

  if (isLoading) {
    return null;
  }

  if (!user) {
    return (
      <>
        <Link href="/servicos" className={cn(buttonVariants({ size: "lg" }))}>
          Ver serviços
        </Link>
        <Link href="/cadastro" className={cn(buttonVariants({ variant: "outline", size: "lg" }))}>
          Criar conta
        </Link>
      </>
    );
  }

  if (user.role === "admin") {
    return (
      <>
        <Link href="/admin/agendamentos" className={cn(buttonVariants({ size: "lg" }))}>
          Ir para o painel
        </Link>
        <Link href="/admin/servicos" className={cn(buttonVariants({ variant: "outline", size: "lg" }))}>
          Gerenciar serviços
        </Link>
      </>
    );
  }

  return (
    <>
      <Link href="/agendar" className={cn(buttonVariants({ size: "lg" }))}>
        Agendar horário
      </Link>
      <Link href="/meus-agendamentos" className={cn(buttonVariants({ variant: "outline", size: "lg" }))}>
        Meus agendamentos
      </Link>
    </>
  );
}
