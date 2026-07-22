# Zelo

![CI](https://github.com/filipeyuan/agendamento-app/actions/workflows/ci.yml/badge.svg)

Sistema de agendamento online full stack para negócios de serviço. Cliente escolhe serviço e horário, admin gerencia serviços e confirma/cancela agendamentos, com regra de conflito de horário.

Projeto construído em fases, cada uma terminando com algo funcionando e implantado (não só código local).

## Stack

- **Frontend:** Next.js (App Router) + TypeScript + Tailwind CSS + SWR ([`/frontend`](./frontend))
- **Backend:** Laravel + Sanctum, API REST com autenticação via token ([`/backend`](./backend))
- **Banco:** SQLite em desenvolvimento, PostgreSQL (Neon) em produção
- **Qualidade:** PHPStan (Larastan, nível 8) + Laravel Pint (PSR-12)

## Deploy

- Frontend: [Vercel](https://agendamento-app-alpha.vercel.app)
- Backend: [Render](https://agendamento-app-2muq.onrender.com) (banco Postgres no Neon)
- Documentação da API: [/docs/api](https://agendamento-app-2muq.onrender.com/docs/api)

**Login de demonstração** (admin): `admin@agendamento.app` / `admin12345`

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
- [ ] **Fase 4**: Diferenciais. ~~Calendário visual (FullCalendar)~~, ~~horário de atendimento configurável~~, ~~assistente de agendamento via IA~~, Google Calendar
- [ ] **Fase 5**: Polimento final. ~~Identidade de produto (nome/copy reais)~~, ilustrações e dashboard de analytics
- [ ] **Fase 6**: Referência visual e mobile. Pesquisar SaaS reais de agendamento/produtividade (paleta de cores, estrutura de home, estilo de componentes) e aplicar o que fizer sentido pro Zelo parecer um produto maduro, não um projeto de estudo. Inclui: badges com mais cor, ícone de cadeado em horário indisponível, ícone de "+" pra adicionar horário, e otimização do modo mobile

Cada subpasta tem seu próprio README com instruções de setup local (sem Docker, se preferir).
