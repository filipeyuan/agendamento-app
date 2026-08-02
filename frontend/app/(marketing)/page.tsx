"use client";

import Image from "next/image";
import { motion, useReducedMotion, type Variants } from "motion/react";
import { ChevronDown, CreditCard, LayoutDashboard, ShieldCheck, Smartphone, Users } from "lucide-react";

import { HeroActions } from "@/components/layout/hero-actions.component";
import { Reveal } from "@/components/motion/reveal.component";

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

function Hero() {
  const shouldReduceMotion = useReducedMotion();

  const heroContainer: Variants = {
    hidden: {},
    show: { transition: { staggerChildren: shouldReduceMotion ? 0.06 : 0.12 } },
  };

  const heroItem: Variants = {
    hidden: { opacity: 0, y: shouldReduceMotion ? 8 : 16 },
    show: {
      opacity: 1,
      y: 0,
      transition: { duration: shouldReduceMotion ? 0.35 : 0.5, ease: [0.16, 1, 0.3, 1] },
    },
  };

  return (
    <section className="hero-dark relative -mt-16 overflow-hidden pt-16">
      <div className="pointer-events-none absolute inset-0 -z-10" aria-hidden>
        <div className="absolute right-[-10rem] top-[-4rem] h-[34rem] w-[34rem] rounded-full bg-[var(--hero-primary)]/20 blur-[120px]" />
      </div>

      <div className="mx-auto grid max-w-6xl gap-14 px-4 py-20 sm:py-24 lg:grid-cols-[1.15fr_0.85fr] lg:items-center lg:py-32">
        <motion.div
          className="flex flex-col items-center gap-7 text-center lg:items-start lg:text-left"
          initial="hidden"
          animate="show"
          variants={heroContainer}
        >
          <motion.h1
            variants={heroItem}
            className="text-6xl font-bold leading-[0.95] tracking-tight sm:text-7xl lg:text-8xl"
          >
            Sua agenda,{" "}
            <span className="relative inline-block">
              <span className="relative z-10">com zelo.</span>
              <span
                className="absolute inset-x-0 bottom-2 -z-0 h-4 -rotate-1 bg-[var(--hero-primary)]/30 sm:h-5 lg:h-6"
                aria-hidden
              />
            </span>
          </motion.h1>

          <motion.p variants={heroItem} className="max-w-lg text-lg text-[var(--hero-muted)]">
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
                className="flex items-center gap-1.5 rounded-full border border-[var(--hero-border)] bg-white/[0.04] px-3.5 py-1.5 text-xs font-medium text-[var(--hero-muted)]"
              >
                <span className="h-1.5 w-1.5 shrink-0 rounded-full bg-[var(--hero-primary)]" />
                {audience}
              </span>
            ))}
          </motion.div>
        </motion.div>

        <div className="relative mx-auto w-full max-w-md lg:mx-0 lg:justify-self-end">
          <motion.div
            initial={{ opacity: 0, y: shouldReduceMotion ? 10 : 24, rotate: -2 }}
            animate={{ opacity: 1, y: 0, rotate: -2 }}
            transition={{
              delay: shouldReduceMotion ? 0.1 : 0.5,
              duration: shouldReduceMotion ? 0.4 : 0.7,
              ease: [0.16, 1, 0.3, 1],
            }}
            className="shadow-elevated-lg overflow-hidden rounded-xl border border-white/10"
            style={{ backgroundColor: "var(--hero-card-bg)" }}
          >
            <div className="flex items-center gap-1.5 border-b border-black/10 bg-black/5 px-4 py-2.5">
              <span className="h-2.5 w-2.5 rounded-full bg-destructive/60" />
              <span className="h-2.5 w-2.5 rounded-full bg-warning/60" />
              <span className="h-2.5 w-2.5 rounded-full bg-success/60" />
            </div>
            <Image
              src="/agenda-preview.png"
              alt="Preview da agenda de agendamentos do Zelo"
              width={1280}
              height={620}
              className="h-auto w-full"
              priority
            />
          </motion.div>

          <motion.div
            initial={{ opacity: 0, y: shouldReduceMotion ? 10 : 24, rotate: 3 }}
            animate={{ opacity: 1, y: shouldReduceMotion ? 0 : [0, -8, 0], rotate: 3 }}
            transition={{
              opacity: { delay: shouldReduceMotion ? 0.3 : 0.9, duration: shouldReduceMotion ? 0.4 : 0.6 },
              y: shouldReduceMotion
                ? { duration: 0.4 }
                : { delay: 1.5, duration: 5, repeat: Infinity, ease: "easeInOut" },
            }}
            className="shadow-elevated-lg absolute -bottom-10 -right-6 w-[42%] overflow-hidden rounded-[1.75rem] border-[6px] border-neutral-900 sm:-right-10"
            style={{ backgroundColor: "var(--hero-card-bg)" }}
          >
            <Image
              src="/booking-mobile-preview.png"
              alt="Preview do fluxo de agendamento do Zelo no celular"
              width={375}
              height={620}
              className="h-auto w-full"
            />
          </motion.div>
        </div>
      </div>
    </section>
  );
}

function StepsSection() {
  return (
    <section className="mx-auto max-w-5xl px-4 py-24 sm:py-28">
      <Reveal>
        <p className="text-center text-sm font-semibold uppercase tracking-[0.2em] text-primary sm:text-left">
          Como funciona
        </p>
      </Reveal>

      <div className="relative mt-10 grid gap-10 sm:grid-cols-3">
        <div className="absolute left-0 right-0 top-5 hidden h-px bg-border sm:block" aria-hidden />

        {steps.map((step, index) => (
          <Reveal key={step.title} delay={index * 0.1}>
            <div className="flex flex-col items-center gap-3 text-center sm:items-start sm:text-left">
              <span className="relative z-10 flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-primary text-sm font-bold text-primary-foreground ring-4 ring-background">
                {index + 1}
              </span>
              <h3 className="text-lg font-semibold text-foreground">{step.title}</h3>
              <p className="text-sm text-muted-foreground">{step.description}</p>
            </div>
          </Reveal>
        ))}
      </div>
    </section>
  );
}

function FeaturesSection() {
  return (
    <section className="border-t border-border py-24 sm:py-28">
      <div className="mx-auto max-w-3xl px-4">
        <Reveal>
          <p className="text-sm font-semibold uppercase tracking-[0.2em] text-primary">
            O que o Zelo resolve
          </p>
        </Reveal>

        <Reveal delay={0.05} className="mt-4">
          <h2 className="text-3xl font-bold tracking-tight text-foreground sm:text-4xl">
            Cada detalhe do fluxo pensado pra não dar dor de cabeça.
          </h2>
        </Reveal>

        <div className="mt-12 border-t border-border">
          {features.map((feature, index) => (
            <Reveal key={feature.title} delay={0.05 * index}>
              <div className="flex items-start gap-4 border-b border-border py-6">
                <span className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-primary/10 text-primary">
                  <feature.icon className="h-5 w-5" />
                </span>
                <div>
                  <h3 className="text-base font-semibold text-foreground">{feature.title}</h3>
                  <p className="mt-1 text-sm text-muted-foreground">{feature.description}</p>
                </div>
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
    <section className="hero-dark relative overflow-hidden py-24 sm:py-32">
      <div className="pointer-events-none absolute inset-0 -z-10" aria-hidden>
        <div className="absolute left-1/2 top-0 h-[28rem] w-[36rem] -translate-x-1/2 rounded-full bg-[var(--hero-primary)]/12 blur-[130px]" />
      </div>

      <div className="mx-auto max-w-5xl px-4">
        <Reveal className="mx-auto max-w-xl text-center">
          <h2 className="text-4xl font-bold tracking-tight sm:text-5xl">Veja o painel em ação</h2>
          <p className="mt-4 text-[var(--hero-muted)]">
            Dashboard com agendamentos por dia, distribuição por status, receita e os serviços
            mais procurados, tudo em tempo real.
          </p>
        </Reveal>

        <Reveal delay={0.1} className="mt-14">
          <div
            className="shadow-elevated-lg mx-auto max-w-4xl overflow-hidden rounded-xl border border-white/10"
            style={{ backgroundColor: "var(--hero-card-bg)" }}
          >
            <Image
              src="/dashboard-preview-v4.png"
              alt="Preview do dashboard de analytics do Zelo, com agendamentos por dia e por status"
              width={1280}
              height={620}
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
    <section className="py-24 sm:py-32">
      <div className="mx-auto max-w-2xl px-4">
        <Reveal>
          <h2 className="text-4xl font-bold tracking-tight text-foreground sm:text-5xl">
            Perguntas frequentes
          </h2>
        </Reveal>

        <div className="mt-12 border-t border-border">
          {faq.map((item, index) => (
            <Reveal key={item.question} delay={Math.min(index * 0.05, 0.3)}>
              <details className="group border-b border-border py-5">
                <summary className="flex cursor-pointer list-none items-center justify-between gap-4 text-base font-medium text-foreground">
                  {item.question}
                  <ChevronDown className="h-4 w-4 shrink-0 text-muted-foreground transition-transform group-open:rotate-180" />
                </summary>
                <p className="mt-3 text-muted-foreground">{item.answer}</p>
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
    <main className="flex-1">
      <Hero />
      <StepsSection />
      <FeaturesSection />
      <DashboardPreviewSection />
      <FaqSection />
    </main>
  );
}
