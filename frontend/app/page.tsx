import Image from "next/image";
import {
  BadgeCheck,
  CalendarClock,
  CheckCircle2,
  ChevronDown,
  LayoutDashboard,
  Scissors,
  ShieldCheck,
  Smartphone,
  Users,
} from "lucide-react";

import { HeroActions } from "@/components/layout/hero-actions.component";
import { SchedulingIllustration } from "@/components/illustrations/scheduling-illustration.component";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { cn } from "@/lib/utils/cn";

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
    span: true,
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

const faq = [
  {
    question: "Como funciona o bloqueio de conflito de horário?",
    answer:
      "Toda confirmação passa por uma trava no banco de dados, dentro de uma transação. Se duas pessoas tentarem agendar o mesmo horário ao mesmo tempo, só a primeira consegue, a segunda recebe um aviso na hora pra escolher outro horário.",
  },
  {
    question: "Dá pra ter múltiplos admins ou múltiplos prestadores?",
    answer:
      "Hoje o Zelo foi pensado pra um negócio só: um horário de atendimento e um catálogo de serviços compartilhado. Vários usuários podem ter papel de admin, mas todos gerenciam a mesma agenda, não agendas independentes por profissional.",
  },
  {
    question: "O Google Calendar sincroniza de verdade?",
    answer:
      "Sim. Ao conectar via OAuth, confirmar um agendamento cria um evento real no Google Calendar do admin, e cancelar remove esse evento. Além disso, horários já ocupados na agenda pessoal do admin no Google bloqueiam esse horário pro cliente.",
  },
  {
    question: "O assistente por IA realmente cria o agendamento, ou só responde perguntas?",
    answer:
      "Ele cria de verdade. O assistente consulta os serviços ativos, checa os horários livres e confirma o agendamento no banco de dados, sempre a partir de dados reais, nunca inventados.",
  },
  {
    question: "Dá pra configurar um horário de atendimento diferente por dia da semana?",
    answer:
      "Sim, o admin define o horário de cada dia da semana (ou marca como fechado) e ainda pode bloquear datas ou intervalos específicos, tipo um feriado ou uma folga.",
  },
];

export default function Home() {
  return (
    <main className="flex-1 overflow-x-hidden">
      <section className="relative overflow-hidden">
        <div className="pointer-events-none absolute inset-0 -z-10" aria-hidden>
          <div className="absolute -left-20 top-[-8rem] h-[26rem] w-[26rem] rounded-full bg-primary/25 blur-[100px]" />
          <div className="absolute right-[-6rem] top-[6rem] h-[22rem] w-[22rem] rounded-full bg-success/20 blur-[100px]" />
          <div className="absolute left-1/3 top-[20rem] h-[18rem] w-[18rem] rounded-full bg-warning/15 blur-[100px]" />
        </div>

        <div className="mx-auto grid max-w-6xl gap-12 px-4 py-16 sm:py-20 lg:grid-cols-2 lg:items-center lg:py-28">
          <div className="flex flex-col items-center gap-6 text-center lg:items-start lg:text-left">
            <h1 className="text-5xl font-semibold tracking-tight text-foreground sm:text-6xl">
              Sua agenda,{" "}
              <span
                className="bg-clip-text text-transparent"
                style={{ backgroundImage: "linear-gradient(90deg, var(--primary), var(--success))" }}
              >
                com zelo.
              </span>
            </h1>

            <p className="max-w-xl text-lg text-muted-foreground">
              Cliente escolhe serviço e horário livre. Admin gerencia tudo em um painel, com
              bloqueio automático de horários conflitantes.
            </p>

            <div className="flex flex-wrap items-center justify-center gap-3 lg:justify-start">
              <HeroActions />
            </div>

            <div className="flex flex-wrap items-center justify-center gap-2 lg:justify-start">
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
          </div>

          <div className="relative flex items-center justify-center">
            <div className="absolute inset-0 -z-10 rounded-full bg-primary/15 blur-[80px]" aria-hidden />
            <SchedulingIllustration className="w-full max-w-md" />
          </div>
        </div>
      </section>

      <section className="mx-auto max-w-4xl px-4 pb-20">
        <div className="relative grid gap-4 sm:grid-cols-3">
          <div
            className="absolute left-0 right-0 top-9 hidden h-px bg-gradient-to-r from-transparent via-border to-transparent sm:block"
            aria-hidden
          />

          {steps.map((step, index) => (
            <Card key={step.title} className="relative">
              <CardHeader className="gap-3">
                <div
                  className={cn(
                    "flex h-9 w-9 items-center justify-center rounded-full border-2 bg-card text-sm font-semibold",
                    step.iconClassName,
                    "border-current"
                  )}
                >
                  {index + 1}
                </div>
                <CardTitle className="text-base">{step.title}</CardTitle>
              </CardHeader>
              <CardContent>
                <p className="text-sm text-muted-foreground">{step.description}</p>
              </CardContent>
            </Card>
          ))}
        </div>
      </section>

      <section className="border-t border-border bg-muted/40 py-20">
        <div className="mx-auto max-w-4xl px-4">
          <h2 className="text-center text-2xl font-semibold tracking-tight text-foreground sm:text-3xl">
            O que o Zelo resolve
          </h2>

          <div className="mt-10 grid gap-4 sm:grid-cols-2">
            {features.map((feature) => (
              <Card
                key={feature.title}
                className={cn(
                  "overflow-hidden",
                  feature.span && "sm:col-span-2 sm:bg-gradient-to-r sm:from-primary/5 sm:to-transparent"
                )}
              >
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

      <section className="py-20">
        <div className="mx-auto max-w-3xl px-4 text-center">
          <h2 className="text-2xl font-semibold tracking-tight text-foreground sm:text-3xl">
            Veja o painel em ação
          </h2>
          <p className="mt-3 text-muted-foreground">
            Dashboard com agendamentos por dia, distribuição por status, receita e os serviços mais
            procurados, tudo em tempo real.
          </p>

          <div className="relative mx-auto mt-10 max-w-2xl">
            <div className="absolute -inset-4 -z-10 rounded-2xl bg-primary/10 blur-2xl" aria-hidden />
            <div className="overflow-hidden rounded-xl border border-border bg-card shadow-xl">
              <div className="flex items-center gap-1.5 border-b border-border bg-muted/50 px-4 py-2.5">
                <span className="h-2.5 w-2.5 rounded-full bg-destructive/60" />
                <span className="h-2.5 w-2.5 rounded-full bg-warning/60" />
                <span className="h-2.5 w-2.5 rounded-full bg-success/60" />
              </div>
              <Image
                src="/dashboard-preview-v3.png"
                alt="Preview do dashboard de analytics do Zelo, com agendamentos por dia e por status"
                width={1280}
                height={565}
                className="h-auto w-full"
              />
            </div>
          </div>
        </div>
      </section>

      <section className="border-t border-border py-20">
        <div className="mx-auto max-w-2xl px-4">
          <h2 className="text-center text-2xl font-semibold tracking-tight text-foreground sm:text-3xl">
            Perguntas frequentes
          </h2>

          <div className="mt-10 flex flex-col gap-3">
            {faq.map((item) => (
              <details
                key={item.question}
                className="group rounded-lg border border-border bg-card px-4 py-3 open:pb-4"
              >
                <summary className="flex cursor-pointer list-none items-center justify-between gap-4 font-medium text-foreground">
                  {item.question}
                  <ChevronDown className="h-4 w-4 shrink-0 text-muted-foreground transition-transform group-open:rotate-180" />
                </summary>
                <p className="mt-2 text-sm text-muted-foreground">{item.answer}</p>
              </details>
            ))}
          </div>
        </div>
      </section>
    </main>
  );
}
