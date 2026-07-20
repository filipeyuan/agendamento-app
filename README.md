# Agendamento App

Sistema de agendamento online full stack — cliente escolhe serviço e horário, admin gerencia serviços e confirma/cancela agendamentos, com regra de conflito de horário.

Projeto construído em fases, cada uma terminando com algo funcionando e implantado (não só código local).

## Stack

- **Frontend:** Next.js (App Router) + TypeScript + Tailwind CSS — [`/frontend`](./frontend)
- **Backend:** Laravel + Sanctum (API REST, autenticação via token) — [`/backend`](./backend)
- **Banco:** SQLite em desenvolvimento, PostgreSQL em produção

## Deploy

- Frontend: Vercel — _link em breve_
- Backend: Railway — _link em breve_

## Fases do projeto

- [x] **Fase 0** — Esqueleto implantado: health-check + auth básico (Sanctum), backend e frontend no ar
- [ ] **Fase 1** — MVP funcional: CRUD de serviços, agendamento com regra de conflito, dashboard admin
- [ ] **Fase 2** — Docker (build real em produção) + testes automatizados + CI (GitHub Actions)
- [ ] **Fase 3** — Documentação de API (OpenAPI/Swagger)
- [ ] **Fase 4** — Diferenciais: integração com Google Calendar + assistente de agendamento via IA

Cada subpasta tem seu próprio README com instruções de setup local.
