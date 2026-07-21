# Frontend — Agendamento App (Next.js)

Frontend do sistema de agendamento, consumindo a API Laravel via REST.

## Stack

- Next.js 16 (App Router) + TypeScript
- Tailwind CSS v4 (design tokens via variáveis CSS)
- SWR (busca e revalidação de dados no client)
- Componentes com variantes via `class-variance-authority`

## Como rodar localmente

```bash
npm install
cp .env.example .env.local
npm run dev
```

Abre em `http://localhost:3000`. Requer o backend rodando (veja `../backend/README.md`).

### Alternativa: Docker

Na raiz do monorepo, `docker-compose up` sobe frontend + backend + Postgres juntos (veja `../README.md`). O `Dockerfile.dev` aqui é só pra isso — não é usado pela Vercel, que builda direto do código.

## Qualidade de código

```bash
npm run lint
npm run build   # inclui checagem de tipos
```

## Variáveis de ambiente

| Variável | Descrição |
|---|---|
| `NEXT_PUBLIC_API_URL` | URL base da API Laravel (ex: `http://localhost:8000/api` em dev, `https://seu-backend.onrender.com/api` em produção) |

## Páginas

| Rota | Acesso | Descrição |
|---|---|---|
| `/` | público | Health-check da API |
| `/servicos` | público | Lista de serviços disponíveis |
| `/login`, `/cadastro` | público | Autenticação |
| `/agendar` | cliente autenticado | Escolhe serviço, data e horário livre |
| `/meus-agendamentos` | cliente autenticado | Agendamentos do cliente, com status |
| `/admin/servicos` | admin | CRUD de serviços |
| `/admin/agendamentos` | admin | Lista com filtros + confirmar/cancelar/concluir |

## Estrutura

- `app/` — rotas (App Router)
- `components/ui/` — kit de componentes (Button, Input, Card, Badge...), sem lógica de negócio
- `components/auth/`, `components/layout/` — componentes de aplicação (`.component.tsx`)
- `lib/api/` — camada de acesso à API (uma função por operação)
- `lib/auth/` — contexto de autenticação, guarda de rota por role, storage do token
- `lib/types/` — uma pasta por entidade (`users/`, `services/`, `appointments/`), espelhando o backend
- `lib/utils/` — helpers (`cn`, formatação de erro, data)

## Design tokens

Cores e raio de borda ficam em variáveis CSS (`app/globals.css`, mapeadas via `@theme`). Pra mudar a cor de um variant (ex: `secondary`), basta editar a variável correspondente — os componentes nunca têm cor "hardcoded".
