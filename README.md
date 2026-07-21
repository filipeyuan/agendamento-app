# Agendamento App

Sistema de agendamento online full stack — cliente escolhe serviço e horário, admin gerencia serviços e confirma/cancela agendamentos, com regra de conflito de horário.

Projeto construído em fases, cada uma terminando com algo funcionando e implantado (não só código local).

## Stack

- **Frontend:** Next.js (App Router) + TypeScript + Tailwind CSS + SWR — [`/frontend`](./frontend)
- **Backend:** Laravel + Sanctum (API REST, autenticação via token) — [`/backend`](./backend)
- **Banco:** SQLite em desenvolvimento, PostgreSQL (Neon) em produção
- **Qualidade:** PHPStan (Larastan, nível 8) + Laravel Pint (PSR-12)

## Deploy

- Frontend: [Vercel](https://agendamento-app-alpha.vercel.app)
- Backend: [Render](https://agendamento-app-2muq.onrender.com) (banco Postgres no Neon)

**Login de demonstração** (admin): `admin@agendamento.app` / `admin12345`

## Fases do projeto

- [x] **Fase 0** — Esqueleto implantado: health-check + auth básico (Sanctum), backend e frontend no ar
- [x] **Fase 1** — MVP funcional: CRUD de serviços, agendamento com regra de conflito, dashboard admin
- [ ] **Fase 2** — Docker (build real em produção) + testes automatizados + CI (GitHub Actions)
- [ ] **Fase 3** — Documentação de API (OpenAPI/Swagger)
- [ ] **Fase 4** — Diferenciais: integração com Google Calendar + assistente de agendamento via IA

Cada subpasta tem seu próprio README com instruções de setup local.
