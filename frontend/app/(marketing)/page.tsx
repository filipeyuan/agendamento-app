"use client";

import Image from "next/image";
import { motion, useReducedMotion, type Variants } from "motion/react";
import {
  CheckCircle2,
  ChevronDown,
  CreditCard,
  LayoutDashboard,
  ShieldCheck,
  Smartphone,
  Sparkles,
  Users,
} from "lucide-react";

import { HeroActions } from "@/components/layout/hero-actions.component";
import { Reveal } from "@/components/motion/reveal.component";
import { SchedulingIllustration } from "@/components/illustrations/scheduling-illustration.component";
import { cn } from "@/lib/utils/cn";

const steps = [
  {
    title: "Escolha o serviço",
    description: "Veja duração e preço de cada serviço disponível.",
  },
  {
    title: "Escolha o horário",
    description: "A agenda mostra só os horários realmente livres.",
  },
  {
    title: "Pronto",
    description: "Acompanhe o status do seu agendamento em tempo real.",
  },
];

const flagshipFeature = {
  icon: ShieldCheck,
  title: "Sem horário duplicado",
  description:
    "A confirmação é validada no servidor, não só na tela. Mesmo com duas pessoas agendando ao mesmo tempo, só uma fica com o horário.",
};

const features = [
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
  {
    icon: CreditCard,
    title: "Pagamento e avisos automáticos",
    description:
      "O cliente paga na hora de agendar (Stripe). A cada mudança de status, ele recebe e-mail e uma notificação no sininho do app.",
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
      "Sim: o Zelo é multi-tenant. Ao se cadastrar como negócio, você ganha sua própria agenda, catálogo de serviços, horário de atendimento e conexão com o Google Calendar, completamente isolados dos outros negócios cadastrados na plataforma.",
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
    question: "Como funciona o pagamento e os avisos?",
    answer:
      "O cliente paga direto no checkout do Stripe ao agendar; o horário só fica reservado enquanto o pagamento estiver em aberto, e é liberado automaticamente se ele não pagar. A cada confirmação, cancelamento ou pagamento aprovado, o cliente recebe um e-mail e uma notificação dentro do app.",
  },
  {
    question: "Dá pra configurar um horário de atendimento diferente por dia da semana?",
    answer:
      "Sim, o admin define o horário de cada dia da semana (ou marca como fechado) e ainda pode bloquear datas ou intervalos específicos, tipo um feriado ou uma folga.",
  },
];

const heroContainer: Variants = {
  hidden: {},
  show: { transition: { staggerChildren: 0.12 } },
};

const heroItem: Variants = {
  hidden: { opacity: 0, y: 16 },
  show: { opacity: 1, y: 0, transition: { duration: 0.5, ease: [0.16, 1, 0.3, 1] } },
};

function Hero() {
  const shouldReduceMotion = useReducedMotion();

  return (
    <section className="relative overflow-hidden">
      <div className="pointer-events-none absolute inset-0 -z-10" aria-hidden>
        <div className="absolute -left-20 top-[-8rem] h-[26rem] w-[26rem] rounded-full bg-primary/25 blur-[100px]" />
        <div className="absolute right-[-6rem] top-[6rem] h-[22rem] w-[22rem] rounded-full bg-success/20 blur-[100px]" />
        <div className="absolute left-1/3 top-[20rem] h-[18rem] w-[18rem] rounded-full bg-warning/15 blur-[100px]" />
      </div>

      <div className="mx-auto grid max-w-6xl gap-16 px-4 py-16 sm:py-20 lg:grid-cols-[1.05fr_0.95fr] lg:items-center lg:py-28">
        <motion.div
          className="flex flex-col items-center gap-6 text-center lg:items-start lg:text-left"
          initial={shouldReduceMotion ? undefined : "hidden"}
          animate={shouldReduceMotion ? undefined : "show"}
          variants={heroContainer}
        >
          <motion.span
            variants={heroItem}
            className="inline-flex items-center gap-1.5 rounded-full border border-primary/20 bg-primary/10 px-3 py-1 text-xs font-medium text-primary"
          >
            <Sparkles className="h-3.5 w-3.5" />
            Agendamento sem dor de cabeça
          </motion.span>

          <motion.h1
            variants={heroItem}
            className="text-5xl font-semibold tracking-tight text-foreground sm:text-6xl"
          >
            Sua agenda,{" "}
            <span className="relative inline-block whitespace-nowrap">
              <span className="relative z-10">com zelo.</span>
              <span
                className="absolute inset-x-0 bottom-1.5 -z-0 h-3 -rotate-1 bg-primary/25 sm:h-4"
                aria-hidden
              />
            </span>
          </motion.h1>

          <motion.p variants={heroItem} className="max-w-xl text-lg text-muted-foreground">
            Cliente escolhe serviço e horário livre. Admin gerencia tudo em um painel, com
            bloqueio automático de horários conflitantes.
          </motion.p>

          <motion.div
            variants={heroItem}
            className="flex flex-wrap items-center justify-center gap-3 lg:justify-start"
          >
            <HeroActions />
          </motion.div>

          <motion.div
            variants={heroItem}
            className="flex flex-wrap items-center justify-center gap-2 lg:justify-start"
          >
            {audiences.map((audience) => (
              <span
                key={audience}
                className="rounded-full border border-border bg-card px-3 py-1 text-xs text-muted-foreground"
              >
                {audience}
              </span>
            ))}
          </motion.div>
        </motion.div>

        <div className="relative mx-auto flex w-full max-w-md items-center justify-center lg:mx-0">
          <motion.div
            className="w-full"
            animate={shouldReduceMotion ? undefined : { y: [0, -10, 0] }}
            transition={
              shouldReduceMotion ? undefined : { duration: 6, repeat: Infinity, ease: "easeInOut" }
            }
          >
            <SchedulingIllustration className="w-full" />
          </motion.div>

          <motion.div
            initial={shouldReduceMotion ? undefined : { opacity: 0, y: 12, rotate: -8 }}
            animate={shouldReduceMotion ? undefined : { opacity: 1, y: 0, rotate: -4 }}
            transition={shouldReduceMotion ? undefined : { delay: 0.9, duration: 0.5 }}
            className="shadow-elevated-lg absolute -bottom-2 left-2 flex items-center gap-2.5 rounded-2xl border border-border bg-card px-4 py-3 sm:left-4"
          >
            <span className="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-success/15 text-success">
              <CheckCircle2 className="h-4 w-4" />
            </span>
            <div className="text-left">
              <p className="text-sm font-semibold text-foreground">Horário confirmado</p>
              <p className="text-xs text-muted-foreground">Sem conflito, garantido pelo servidor</p>
            </div>
          </motion.div>
        </div>
      </div>
    </section>
  );
}

function StepsSection() {
  return (
    <section className="mx-auto max-w-5xl px-4 pb-24 pt-6">
      <div className="grid gap-10 sm:grid-cols-3">
        {steps.map((step, index) => (
          <Reveal key={step.title} delay={index * 0.1}>
            <div className={cn("relative", index === 1 && "sm:mt-8", index === 2 && "sm:mt-16")}>
              <span
                className="pointer-events-none absolute -left-1 -top-8 select-none font-heading text-7xl font-bold text-foreground/5"
                aria-hidden
              >
                {String(index + 1).padStart(2, "0")}
              </span>
              <h3 className="relative text-lg font-semibold text-foreground">{step.title}</h3>
              <p className="relative mt-1.5 text-sm text-muted-foreground">{step.description}</p>
            </div>
          </Reveal>
        ))}
      </div>
    </section>
  );
}

function FeaturesSection() {
  return (
    <section className="border-t border-border bg-muted/40 py-24">
      <div className="mx-auto max-w-5xl px-4">
        <div className="grid gap-4 lg:grid-cols-[minmax(0,22rem)_1fr] lg:items-end lg:gap-8">
          <Reveal>
            <h2 className="text-3xl font-semibold tracking-tight text-foreground sm:text-4xl">
              O que o Zelo resolve
            </h2>
          </Reveal>
          <Reveal delay={0.05}>
            <p className="text-muted-foreground lg:text-right">
              Cada detalhe do fluxo de agendamento pensado pra não dar dor de cabeça, nem pro
              cliente nem pra quem administra.
            </p>
          </Reveal>
        </div>

        <Reveal delay={0.1} className="mt-10">
          <div className="flex flex-col gap-4 rounded-2xl border border-primary/15 bg-primary/5 px-6 py-7 sm:flex-row sm:items-center sm:gap-8">
            <flagshipFeature.icon className="h-9 w-9 shrink-0 text-primary" />
            <div>
              <h3 className="text-lg font-semibold text-foreground">{flagshipFeature.title}</h3>
              <p className="mt-1.5 text-sm text-muted-foreground">{flagshipFeature.description}</p>
            </div>
          </div>
        </Reveal>

        <div className="mt-4 columns-1 gap-4 sm:columns-2">
          {features.map((feature, index) => (
            <Reveal key={feature.title} delay={0.05 * index} className="mb-4 break-inside-avoid">
              <div className="shadow-[var(--elevation-sm)] hover:shadow-[var(--elevation-md)] rounded-2xl border border-border bg-card px-5 py-5 transition-all duration-200 hover:-translate-y-0.5">
                <div className="flex items-center gap-2.5">
                  <feature.icon className="h-5 w-5 shrink-0 text-primary" />
                  <h3 className="font-semibold text-foreground">{feature.title}</h3>
                </div>
                <p className="mt-2 text-sm text-muted-foreground">{feature.description}</p>
              </div>
            </Reveal>
          ))}
        </div>
      </div>
    </section>
  );
}

function DashboardPreviewSection() {
  return (
    <section className="overflow-hidden py-24">
      <div className="mx-auto grid max-w-6xl gap-10 px-4 lg:grid-cols-[0.85fr_1.15fr] lg:items-center">
        <Reveal>
          <h2 className="text-3xl font-semibold tracking-tight text-foreground sm:text-4xl">
            Veja o painel em ação
          </h2>
          <p className="mt-4 text-muted-foreground">
            Dashboard com agendamentos por dia, distribuição por status, receita e os serviços
            mais procurados, tudo em tempo real.
          </p>
        </Reveal>

        <Reveal delay={0.1}>
          <div className="shadow-elevated-lg -rotate-1 overflow-hidden rounded-xl border border-border bg-card lg:mr-[-4rem]">
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
        </Reveal>
      </div>
    </section>
  );
}

function FaqSection() {
  return (
    <section className="border-t border-border py-24">
      <div className="mx-auto max-w-2xl px-4">
        <Reveal>
          <h2 className="text-3xl font-semibold tracking-tight text-foreground sm:text-4xl">
            Perguntas frequentes
          </h2>
        </Reveal>

        <div className="mt-10 flex flex-col gap-3">
          {faq.map((item, index) => (
            <Reveal key={item.question} delay={Math.min(index * 0.05, 0.3)}>
              <details className="group rounded-lg border border-border bg-card px-4 py-3 open:pb-4">
                <summary className="flex cursor-pointer list-none items-center justify-between gap-4 font-medium text-foreground">
                  {item.question}
                  <ChevronDown className="h-4 w-4 shrink-0 text-muted-foreground transition-transform group-open:rotate-180" />
                </summary>
                <p className="mt-2 text-sm text-muted-foreground">{item.answer}</p>
              </details>
            </Reveal>
          ))}
        </div>
      </div>
    </section>
  );
}

export default function Home() {
  return (
    <main className="flex-1 overflow-x-hidden">
      <Hero />
      <StepsSection />
      <FeaturesSection />
      <DashboardPreviewSection />
      <FaqSection />
    </main>
  );
}
