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

## Como rodar tudo local com Docker

```bash
docker-compose up
```

Sobe backend (`:8000`), frontend (`:3000`) e PostgreSQL, com hot-reload nos dois lados (código local é montado dentro dos containers). Não precisa de PHP/Node/Postgres instalados na máquina — só Docker.

## Fases do projeto

- [x] **Fase 0** — Esqueleto implantado: health-check + auth básico (Sanctum), backend e frontend no ar
- [x] **Fase 1** — MVP funcional: CRUD de serviços, agendamento com regra de conflito, dashboard admin
- [ ] **Fase 2** — Docker Compose pra dev local + testes automatizados + CI (GitHub Actions)
- [ ] **Fase 3** — Documentação de API (OpenAPI/Swagger)
- [ ] **Fase 4** — Diferenciais: Google Calendar + assistente de agendamento via IA + calendário visual (FullCalendar)
- [ ] **Fase 5** — Polimento final: identidade de produto (nome/copy reais), ilustrações e dashboard de analytics

Cada subpasta tem seu próprio README com instruções de setup local (sem Docker, se preferir).
