# Zelo

![CI](https://github.com/filipeyuan/agendamento-app/actions/workflows/ci.yml/badge.svg)

Sistema de agendamento online full stack, multi-tenant, para negócios de serviço (salões, clínicas, barbearias, estúdios). Qualquer negócio se cadastra e ganha sua própria agenda isolada; cliente escolhe o negócio, o serviço e o horário livre; admin gerencia tudo em um painel, com regra de conflito de horário, sincronia com Google Calendar e assistente de agendamento via IA.

Projeto construído em fases, cada uma terminando com algo funcionando e implantado (não só código local).

![Home do Zelo](./docs/screenshot-home.png)

## Funcionalidades

- **Multi-tenant de verdade**: qualquer pessoa se cadastra como negócio e ganha agenda, serviços, horário de atendimento e conexão com o Google Calendar isolados dos demais negócios da plataforma; testes dedicados provam que um negócio não vê nem edita nada de outro
- **Agendamento sem conflito**: reserva de horário protegida por transação com lock no banco, evita dois clientes marcando o mesmo horário
- **Horário de atendimento configurável**: horário por dia da semana + bloqueios manuais de data/hora (feriado, viagem, manutenção)
- **Assistente de agendamento via IA**: chat com Google Gemini que consulta serviços, verifica horários livres e cria o agendamento de verdade via function calling, não é só um chat decorativo
- **Google Calendar**: agendamentos confirmados viram evento real no Google Calendar do admin, e compromissos pessoais já existentes bloqueiam horários automaticamente
- **Pagamento na hora de agendar**: checkout real via Stripe, o horário só é reservado enquanto o pagamento estiver pendente e é liberado automaticamente se o cliente não pagar; cancelar um agendamento pago reembolsa automaticamente via Stripe
- **Agendamento recorrente**: repetição semanal (até 12 vezes), pago numa única sessão de checkout; se qualquer ocorrência conflitar, nenhuma é criada
- **Cliente remarca/cancela sozinho**: até 2h antes do horário, sem precisar falar com o admin
- **Notificações**: e-mail (pagamento confirmado, agendamento confirmado/cancelado/remarcado) via Resend, mais um sino de notificações in-app com contagem de não lidas
- **Dashboard de analytics**: volume de agendamentos por dia, distribuição por status, receita e serviços mais procurados
- **Painel admin completo**: CRUD de serviços, gestão de agendamentos com calendário visual (FullCalendar), confirmar/cancelar/concluir

![Dashboard de analytics](./docs/screenshot-dashboard.png)

## Stack

- **Frontend:** Next.js (App Router) + TypeScript + Tailwind CSS + SWR ([`/frontend`](./frontend))
- **Backend:** Laravel + Sanctum, API REST com autenticação via token ([`/backend`](./backend))
- **Banco:** SQLite em desenvolvimento, PostgreSQL (Neon) em produção
- **IA:** Google Gemini (function calling)
- **Integrações:** Google Calendar API (OAuth2), Stripe (pagamento), Resend (e-mail transacional)
- **Qualidade:** PHPStan (Larastan, nível 8) + Laravel Pint (PSR-12), 106 testes PHPUnit + 17 testes Jest/RTL, CI no GitHub Actions a cada push

## Deploy

- Frontend: [Vercel](https://zelo-agendamento.vercel.app)
- Backend: [Render](https://agendamento-app-2muq.onrender.com) (banco Postgres no Neon)
- Documentação da API: [/docs/api](https://agendamento-app-2muq.onrender.com/docs/api)

## Como rodar tudo local com Docker

```bash
docker-compose up
```

Sobe backend (`:8000`), frontend (`:3000`) e PostgreSQL, com hot-reload nos dois lados (código local é montado dentro dos containers). Não precisa de PHP/Node/Postgres instalados na máquina, só Docker.

## Fases do projeto

- [x] **Fase 0**: Esqueleto implantado, health-check + auth básico (Sanctum), backend e frontend no ar
- [x] **Fase 1**: MVP funcional, CRUD de serviços, agendamento com regra de conflito, dashboard admin
- [x] **Fase 2**: Docker Compose pra dev local + testes automatizados + CI (GitHub Actions)
- [x] **Fase 3**: Documentação de API (OpenAPI/Swagger)
- [x] **Fase 4**: Diferenciais. Calendário visual, horário de atendimento configurável, assistente de agendamento via IA, Google Calendar
- [x] **Fase 5**: Polimento final. Identidade de produto (nome/copy reais), ilustrações e dashboard de analytics
- [x] **Fase 6**: Referência visual e mobile. Redesign de UI/UX inspirado em SaaS reais, badges e ícones de estado, otimização do modo mobile
- [x] **Fase 7**: Pagamento e notificações. Checkout via Stripe, e-mail transacional (Resend), notificações in-app e mensagens de validação em português
- [x] **Fase 8**: Autoatendimento do cliente. Cancelar/remarcar o próprio agendamento, agendamento recorrente semanal, e ajustes de UX (confirmação antes de cancelar, histórico de notificações)
- [x] **Fase 9**: Multi-tenant. Reembolso automático via Stripe ao cancelar um agendamento pago, modelo de dados isolado por negócio, cadastro self-service de negócio, navegação multi-negócio pro cliente e testes dedicados de isolamento entre negócios

Cada subpasta tem seu próprio README com instruções de setup local (sem Docker, se preferir).
