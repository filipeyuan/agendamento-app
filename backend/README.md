# Backend — Agendamento App (API Laravel)

API REST em Laravel para o sistema de agendamento. Autenticação via Laravel Sanctum (tokens, não cookies), pensada pra ser consumida por um frontend em outro domínio (Next.js na Vercel).

## Stack

- PHP 8.5 + Laravel 13
- Laravel Sanctum (autenticação via Bearer Token)
- SQLite em desenvolvimento local / PostgreSQL em produção

## Como rodar localmente

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate
php artisan serve
```

A API sobe em `http://127.0.0.1:8000`.

## Variáveis de ambiente

| Variável | Descrição |
|---|---|
| `APP_URL` | URL base da API |
| `FRONTEND_URLS` | URLs do frontend com permissão de CORS, separadas por vírgula (ex: `http://localhost:3000,https://seu-projeto.vercel.app`) |
| `DB_CONNECTION` | `sqlite` local, `pgsql` em produção |

## Endpoints (Fase 0)

| Método | Rota | Auth | Descrição |
|---|---|---|---|
| GET | `/api/health` | não | Verifica se a API está no ar |
| POST | `/api/register` | não | Cria um usuário (sempre role `client`) |
| POST | `/api/login` | não | Autentica e retorna um token Sanctum |
| POST | `/api/logout` | sim | Revoga o token atual |
| GET | `/api/me` | sim | Retorna o usuário autenticado |

Rotas autenticadas exigem o header `Authorization: Bearer {token}`.
