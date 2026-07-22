import {
  BadgeCheck,
  CalendarClock,
  CheckCircle2,
  LayoutDashboard,
  Scissors,
  ShieldCheck,
  Smartphone,
  Users,
} from "lucide-react";

import { HeroActions } from "@/components/layout/hero-actions.component";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { apiFetch } from "@/lib/api/client";
import { cn } from "@/lib/utils/cn";

type HealthResponse = {
  status: "ok";
  timestamp: string;
};

async function getApiHealth() {
  try {
    const res = await apiFetch("/health");
    if (!res.ok) return null;
    return (await res.json()) as HealthResponse;
  } catch {
    return null;
  }
}

const steps = [
  {
    icon: Scissors,
    title: "Escolha o serviço",
    description: "Veja duração e preço de cada serviço disponível.",
    iconClassName: "text-primary",
  },
  {
    icon: CalendarClock,
    title: "Escolha o horário",
    description: "A agenda mostra só os horários realmente livres.",
    iconClassName: "text-warning",
  },
  {
    icon: CheckCircle2,
    title: "Pronto",
    description: "Acompanhe o status do seu agendamento em tempo real.",
    iconClassName: "text-success",
  },
];

const features = [
  {
    icon: ShieldCheck,
    title: "Sem horário duplicado",
    description:
      "A confirmação é validada no servidor, não só na tela. Mesmo com duas pessoas agendando ao mesmo tempo, só uma fica com o horário.",
  },
  {
    icon: LayoutDashboard,
    title: "Painel completo pro seu negócio",
    description:
      "Veja a semana, o mês ou a lista de agendamentos, com status colorido, e confirme ou cancele em um clique.",
  },
  {
    icon: Users,
    title: "Um lugar pra cada perfil",
    description:
      "Clientes agendam e acompanham o próprio histórico. Você gerencia serviços, preços e confirmações.",
  },
  {
    icon: Smartphone,
    title: "Funciona em qualquer lugar",
    description: "É só abrir no navegador, do celular ou do computador. Sem instalar nada.",
  },
];

const audiences = ["Salões de beleza", "Barbearias", "Clínicas e consultórios", "Estúdios", "Pet shops"];

export default async function Home() {
  const health = await getApiHealth();

  return (
    <main className="flex-1">
      <section className="mx-auto flex max-w-3xl flex-col items-center gap-6 px-4 py-20 text-center">
        <h1 className="text-4xl font-semibold tracking-tight text-foreground sm:text-5xl">
          Sua agenda, com zelo.
        </h1>

        <p className="max-w-xl text-lg text-muted-foreground">
          Cliente escolhe serviço e horário livre. Admin gerencia tudo em um painel, com
          bloqueio automático de horários conflitantes.
        </p>

        <div className="mt-2 flex flex-wrap items-center justify-center gap-3">
          <HeroActions />
        </div>

        <div className="mt-4 flex items-center gap-2 text-xs text-muted-foreground">
          <span
            className={cn(
              "h-2 w-2 rounded-full",
              health ? "bg-success" : "bg-destructive"
            )}
          />
          API {health ? "online" : "offline"}
          {health && ` · última verificação ${new Date(health.timestamp).toLocaleTimeString("pt-BR")}`}
        </div>

        <div className="mt-2 flex flex-wrap items-center justify-center gap-2">
          {audiences.map((audience) => (
            <span
              key={audience}
              className="flex items-center gap-1.5 rounded-full border border-border bg-card px-3 py-1 text-xs text-muted-foreground"
            >
              <BadgeCheck className="h-3.5 w-3.5 text-primary" />
              {audience}
            </span>
          ))}
        </div>
      </section>

      <section className="mx-auto grid max-w-4xl gap-4 px-4 pb-20 sm:grid-cols-3">
        {steps.map((step) => (
          <Card key={step.title}>
            <CardHeader>
              <step.icon className={cn("h-6 w-6", step.iconClassName)} />
              <CardTitle className="text-base">{step.title}</CardTitle>
            </CardHeader>
            <CardContent>
              <p className="text-sm text-muted-foreground">{step.description}</p>
            </CardContent>
          </Card>
        ))}
      </section>

      <section className="border-t border-border bg-muted/40 py-20">
        <div className="mx-auto max-w-4xl px-4">
          <h2 className="text-center text-2xl font-semibold tracking-tight text-foreground sm:text-3xl">
            O que o Zelo resolve
          </h2>

          <div className="mt-10 grid gap-4 sm:grid-cols-2">
            {features.map((feature) => (
              <Card key={feature.title}>
                <CardHeader className="flex-row items-center gap-3 space-y-0">
                  <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-primary/10">
                    <feature.icon className="h-5 w-5 text-primary" />
                  </div>
                  <CardTitle className="text-base">{feature.title}</CardTitle>
                </CardHeader>
                <CardContent>
                  <p className="text-sm text-muted-foreground">{feature.description}</p>
                </CardContent>
              </Card>
            ))}
          </div>
        </div>
      </section>
    </main>
  );
}
