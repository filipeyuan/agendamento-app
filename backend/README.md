# Backend: Zelo (API Laravel)

API REST em Laravel para o sistema de agendamento. Autenticação via Laravel Sanctum (tokens, não cookies), pensada pra ser consumida por um frontend em outro domínio (Next.js na Vercel).

## Stack

- PHP 8.4+ (produção roda em 8.5) + Laravel 13
- Laravel Sanctum (autenticação via Bearer Token)
- SQLite em desenvolvimento local / PostgreSQL (Neon) em produção
- PHPStan (Larastan, nível 8) + Laravel Pint (PSR-12)
- Documentação OpenAPI gerada automaticamente com [Scramble](https://scramble.dedoc.co)

## Como rodar localmente

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
php artisan serve
```

A API sobe em `http://127.0.0.1:8000`. O seeder cria um admin (`admin@agendamento.app` / `admin12345`, configurável via `ADMIN_EMAIL`/`ADMIN_PASSWORD`) e alguns serviços de exemplo.

### Alternativa: Docker (sem precisar de PHP/Postgres instalados)

Na raiz do monorepo:

```bash
docker-compose up
```

O `backend/Dockerfile.dev` é só pra isso (dev local, hot-reload via bind mount). O `backend/Dockerfile` (sem `.dev`) é o de produção, usado pelo Render. Não roda com `docker-compose`.

## Qualidade de código

```bash
./vendor/bin/pint --test       # PSR-12
./vendor/bin/phpstan analyse --memory-limit=1G   # análise estática (nível 8)
php artisan test               # suíte de testes (PHPUnit)
```

Cobertura de testes: autenticação (registro, login, logout), CRUD de serviços com as regras de autorização admin/cliente, fluxo de agendamento (incluindo o bloqueio de horários conflitantes), atualização de status pelo admin, e horário de atendimento/bloqueios de agenda.

## Variáveis de ambiente

| Variável | Descrição |
|---|---|
| `APP_URL` | URL base da API |
| `FRONTEND_URLS` | URLs do frontend com permissão de CORS, separadas por vírgula (ex: `http://localhost:3000,https://seu-projeto.vercel.app`) |
| `DB_CONNECTION` | `sqlite` local, `pgsql` em produção |
| `DB_URL` | Em produção, connection string completa do Postgres (ex: Neon) |
| `ADMIN_EMAIL` / `ADMIN_PASSWORD` | Credenciais do admin criado pelo seeder |
| `BOOKING_HOURS_START` / `BOOKING_HOURS_END` | Horário padrão usado só na primeira vez que o seeder cria o horário de atendimento (depois disso, o horário fica no banco e é editado por `/api/admin/business-hours`) |

## Documentação da API

A documentação completa e interativa (OpenAPI 3.1, gerada a partir do próprio código: rotas, Form Requests e API Resources) fica em `/docs/api`. Localmente: `http://127.0.0.1:8000/docs/api`. O JSON da especificação fica em `/docs/api.json`.

## Endpoints

Rotas autenticadas exigem o header `Authorization: Bearer {token}`. A tabela abaixo é um resumo rápido. A referência completa está em `/docs/api`.

### Autenticação

| Método | Rota | Auth | Descrição |
|---|---|---|---|
| GET | `/api/health` | não | Verifica se a API está no ar |
| POST | `/api/register` | não | Cria um usuário (sempre role `client`) |
| POST | `/api/login` | não | Autentica e retorna um token Sanctum |
| POST | `/api/logout` | sim | Revoga o token atual |
| GET | `/api/me` | sim | Retorna o usuário autenticado |

### Serviços

| Método | Rota | Auth | Descrição |
|---|---|---|---|
| GET | `/api/services` | não | Lista serviços ativos |
| GET | `/api/services/{service}/available-slots?date=YYYY-MM-DD` | não | Horários livres de um serviço num dia |
| GET | `/api/admin/services` | admin | Lista todos os serviços (inclusive inativos) |
| POST | `/api/admin/services` | admin | Cria um serviço |
| PUT | `/api/admin/services/{service}` | admin | Atualiza um serviço |
| DELETE | `/api/admin/services/{service}` | admin | Remove um serviço |

### Agendamentos

| Método | Rota | Auth | Descrição |
|---|---|---|---|
| POST | `/api/appointments` | sim | Cria um agendamento (retorna 409 em caso de conflito de horário) |
| GET | `/api/appointments/mine` | sim | Lista os agendamentos do usuário autenticado |
| GET | `/api/admin/appointments?date=&from=&to=&status=` | admin | Lista todos os agendamentos, com filtros opcionais (data exata ou intervalo, e status) |
| PATCH | `/api/admin/appointments/{appointment}/status` | admin | Atualiza o status (`confirmed`, `cancelled`, `completed`) |

### Horário de atendimento e bloqueios

| Método | Rota | Auth | Descrição |
|---|---|---|---|
| GET | `/api/admin/business-hours` | admin | Lista o horário de atendimento dos 7 dias da semana |
| PUT | `/api/admin/business-hours` | admin | Atualiza o horário de atendimento (os 7 dias de uma vez) |
| GET | `/api/admin/schedule-blocks?from=&to=` | admin | Lista os bloqueios de horário, com filtro opcional de período |
| POST | `/api/admin/schedule-blocks` | admin | Cria um bloqueio (dia inteiro ou um intervalo específico) |
| DELETE | `/api/admin/schedule-blocks/{scheduleBlock}` | admin | Remove um bloqueio |

## Regra de conflito de horário

`App\Services\BookingService` calcula os horários livres a partir do horário de atendimento do dia da semana e dos bloqueios cadastrados (ambos configuráveis pelo admin, guardados em `business_hours` e `schedule_blocks`) menos os agendamentos ativos, e valida o conflito de novo no momento da criação (dentro de uma transação com lock), pra evitar corrida entre duas requisições simultâneas.
