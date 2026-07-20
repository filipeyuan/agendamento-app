# Frontend — Agendamento App (Next.js)

Frontend do sistema de agendamento, consumindo a API Laravel via REST.

## Stack

- Next.js 16 (App Router) + TypeScript
- Tailwind CSS

## Como rodar localmente

```bash
npm install
cp .env.example .env.local
npm run dev
```

Abre em `http://localhost:3000`. Requer o backend rodando (veja `../backend/README.md`).

## Variáveis de ambiente

| Variável | Descrição |
|---|---|
| `NEXT_PUBLIC_API_URL` | URL base da API Laravel (ex: `http://localhost:8000/api` em dev, `https://seu-backend.up.railway.app/api` em produção) |

## Estrutura

- `app/` — rotas (App Router)
- `lib/api/` — wrapper de acesso à API
