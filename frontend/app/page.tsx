import Link from "next/link";
import { CalendarClock, CheckCircle2, Scissors, Sparkles } from "lucide-react";

import { Badge } from "@/components/ui/badge";
import { buttonVariants } from "@/components/ui/button";
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

export default async function Home() {
  const health = await getApiHealth();

  return (
    <main className="flex-1">
      <section className="mx-auto flex max-w-3xl flex-col items-center gap-6 px-4 py-20 text-center">
        <Badge variant="secondary" className="gap-1.5">
          <Sparkles className="h-3.5 w-3.5" />
          Portfólio · Next.js + Laravel
        </Badge>

        <h1 className="text-4xl font-semibold tracking-tight text-foreground sm:text-5xl">
          Agendamento sem dor de cabeça
        </h1>

        <p className="max-w-xl text-lg text-muted-foreground">
          Cliente escolhe serviço e horário livre, admin gerencia tudo em um dashboard —
          com bloqueio automático de horários conflitantes.
        </p>

        <div className="mt-2 flex flex-wrap items-center justify-center gap-3">
          <Link href="/servicos" className={cn(buttonVariants({ size: "lg" }))}>
            Ver serviços
          </Link>
          <Link href="/cadastro" className={cn(buttonVariants({ variant: "outline", size: "lg" }))}>
            Criar conta
          </Link>
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
    </main>
  );
}
