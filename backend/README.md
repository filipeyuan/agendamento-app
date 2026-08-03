# Backend: Zelo (API Laravel)

API REST em Laravel para o sistema de agendamento. Multi-tenant: cada negócio cadastrado tem seus próprios serviços, horário de atendimento, agendamentos e conexão com o Google Calendar, completamente isolados dos demais. Autenticação via Laravel Sanctum (tokens, não cookies), pensada pra ser consumida por um frontend em outro domínio (Next.js na Vercel).

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

A API sobe em `http://127.0.0.1:8000`. O seeder cria **2 negócios de demonstração** (cada um com seu admin, horário de atendimento e serviços): o padrão (`admin@agendamento.app` / `admin12345`, configurável via `ADMIN_EMAIL`/`ADMIN_PASSWORD`) e um segundo (`admin.clinica@zelo.test` / `demo12345`), pra provar visualmente o isolamento entre negócios.

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

Cobertura de testes: autenticação (registro como cliente ou como admin dono de um negócio novo, login, logout), autoatendimento de conta (editar perfil, trocar senha, desativar com reativação no login seguinte, excluir permanentemente, bloqueio se houver agendamento futuro pendente), CRUD de serviços com as regras de autorização admin/cliente, fluxo de agendamento (incluindo o bloqueio de horários conflitantes), atualização de status pelo admin, cliente cancelando/remarcando o próprio agendamento (com a janela mínima de antecedência), agendamento recorrente (inclusive rejeitando a série inteira se uma ocorrência conflitar), horário de atendimento/bloqueios de agenda, o assistente de agendamento via IA (incluindo a cota diária de mensagens no plano Free), a sincronização com o Google Calendar (ambos com as respectivas APIs simuladas nos testes), o pagamento via Stripe (checkout, webhook com verificação de assinatura, expiração, cobrança de série recorrente numa única sessão), reembolso automático ao cancelar um agendamento pago, assinatura do plano Pro via Stripe Billing (checkout, portal de cobrança, webhook de ativação/cancelamento da assinatura) e os limites reais do plano Free, notificações in-app, o dashboard de analytics, e um conjunto dedicado de testes provando o isolamento entre negócios (um admin não vê nem edita nada de outro negócio, mesmo com token válido).

## Variáveis de ambiente

| Variável | Descrição |
|---|---|
| `APP_URL` | URL base da API |
| `FRONTEND_URLS` | URLs do frontend com permissão de CORS, separadas por vírgula (ex: `http://localhost:3000,https://seu-projeto.vercel.app`) |
| `DB_CONNECTION` | `sqlite` local, `pgsql` em produção |
| `DB_URL` | Em produção, connection string completa do Postgres (ex: Neon) |
| `ADMIN_EMAIL` / `ADMIN_PASSWORD` | Credenciais do admin criado pelo seeder |
| `BOOKING_HOURS_START` / `BOOKING_HOURS_END` | Horário padrão usado só na primeira vez que o seeder cria o horário de atendimento (depois disso, o horário fica no banco e é editado por `/api/admin/business-hours`) |
| `BOOKING_CLIENT_ACTION_WINDOW_HOURS` | Quantas horas de antecedência o cliente precisa ter pra cancelar/remarcar sozinho (default `2`) |
| `BOOKING_MAX_RECURRING_OCCURRENCES` | Máximo de ocorrências semanais num agendamento recorrente (default `12`) |
| `GEMINI_API_KEY` | Chave da API do Gemini (gratuita em [aistudio.google.com/app/apikey](https://aistudio.google.com/app/apikey)), usada pelo assistente de agendamento via IA. Sem ela, o assistente responde avisando que ainda não foi configurado |
| `GEMINI_MODEL` | Modelo do Gemini usado pelo assistente (default `gemini-flash-lite-latest`) |
| `GOOGLE_CLIENT_ID` / `GOOGLE_CLIENT_SECRET` | Credenciais OAuth criadas no Google Cloud Console (Credentials > OAuth Client ID, tipo "Web application"), usadas pra sincronizar com o Google Calendar do admin |
| `GOOGLE_REDIRECT_URI` | URL de callback cadastrada no OAuth Client ID (ex: `http://localhost:8000/api/google-calendar/callback` local, ou a URL do Render em produção) |
| `STRIPE_SECRET_KEY` / `STRIPE_PUBLISHABLE_KEY` | Chaves de teste do Stripe ([dashboard.stripe.com/test/apikeys](https://dashboard.stripe.com/test/apikeys)), usadas pra criar a sessão de checkout do pagamento do agendamento |
| `STRIPE_WEBHOOK_SECRET` | Segredo de assinatura do endpoint de webhook cadastrado em [dashboard.stripe.com/test/webhooks](https://dashboard.stripe.com/test/webhooks) (local, use o Stripe CLI: `stripe listen --forward-to localhost:8000/api/stripe/webhook`, que gera um segredo próprio pra dev) |
| `RESEND_API_KEY` | Chave da API do [Resend](https://resend.com/api-keys), usada pra enviar os e-mails transacionais (`MAIL_MAILER=resend`). Sem domínio verificado, só entrega e-mail pro próprio dono da conta |
| `APP_LOCALE` | `pt_BR`, define o idioma das mensagens de validação (erros de formulário, autenticação etc) |

## Documentação da API

A documentação completa e interativa (OpenAPI 3.1, gerada a partir do próprio código: rotas, Form Requests e API Resources) fica em `/docs/api`. Localmente: `http://127.0.0.1:8000/docs/api`. O JSON da especificação fica em `/docs/api.json`.

## Endpoints

Rotas autenticadas exigem o header `Authorization: Bearer {token}`. A tabela abaixo é um resumo rápido. A referência completa está em `/docs/api`.

### Autenticação

| Método | Rota | Auth | Descrição |
|---|---|---|---|
| GET | `/api/health` | não | Verifica se a API está no ar |
| POST | `/api/register` | não | Cria um usuário. `account_type: "client"` cria um cliente comum; `account_type: "business"` + `business_name` cria um admin dono de um negócio novo |
| POST | `/api/login` | não | Autentica e retorna um token Sanctum |
| POST | `/api/logout` | sim | Revoga o token atual |
| GET | `/api/me` | sim | Retorna o usuário autenticado (inclui o negócio, se for admin) |
| PUT | `/api/me` | sim | Atualiza nome, e-mail e telefone |
| PUT | `/api/me/password` | sim | Troca a senha (exige a senha atual) |
| PATCH | `/api/me/deactivate` | sim | Desativa a conta (revoga todos os tokens); reativa automaticamente no próximo login |
| DELETE | `/api/me` | sim | Exclui a conta permanentemente. Bloqueado se houver agendamento futuro ativo (do cliente, ou do negócio inteiro no caso de admin); exclui o negócio junto se for o admin |

### Negócios (multi-tenant)

| Método | Rota | Auth | Descrição |
|---|---|---|---|
| GET | `/api/businesses` | não | Lista os negócios cadastrados na plataforma |
| GET | `/api/businesses/{business}` | não | Mostra um negócio pelo slug |

### Serviços

| Método | Rota | Auth | Descrição |
|---|---|---|---|
| GET | `/api/services?business={slug}` | não | Lista os serviços ativos de um negócio (`business` é obrigatório) |
| GET | `/api/services/{service}/available-slots?date=YYYY-MM-DD` | não | Horários livres de um serviço num dia |
| GET | `/api/admin/services` | admin | Lista todos os serviços (inclusive inativos) |
| POST | `/api/admin/services` | admin | Cria um serviço |
| PUT | `/api/admin/services/{service}` | admin | Atualiza um serviço |
| DELETE | `/api/admin/services/{service}` | admin | Remove um serviço |

### Agendamentos

| Método | Rota | Auth | Descrição |
|---|---|---|---|
| POST | `/api/appointments` | sim | Cria um agendamento (409 em caso de conflito). Aceita `recurring_occurrences` opcional pra criar uma série semanal recorrente numa única sessão de pagamento |
| GET | `/api/appointments/mine` | sim | Lista os agendamentos do usuário autenticado |
| PATCH | `/api/appointments/{appointment}/cancel` | sim (dono) | Cliente cancela o próprio agendamento, até a janela mínima de antecedência (`client_action_window_hours`) |
| PATCH | `/api/appointments/{appointment}/reschedule` | sim (dono) | Cliente remarca o próprio agendamento pra um novo horário livre |
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

### Assistente de agendamento via IA

| Método | Rota | Auth | Descrição |
|---|---|---|---|
| POST | `/api/assistant/chat` | sim | Envia `business` (slug) + o histórico da conversa e recebe a resposta do assistente |

O assistente usa a API do Gemini com function calling: ele mesmo decide quando consultar os serviços ativos, checar horários livres e criar o agendamento (`App\Services\AssistantService`), sempre a partir de dados reais do banco (só do negócio informado), nunca inventados.

### Google Calendar

| Método | Rota | Auth | Descrição |
|---|---|---|---|
| GET | `/api/admin/google-calendar/connect` | admin | Retorna a URL de consentimento OAuth do Google |
| GET | `/api/google-calendar/callback` | não (Google redireciona aqui) | Troca o código pelo token e salva a conexão |
| GET | `/api/admin/google-calendar/status` | admin | Se o Google Calendar está conectado |
| DELETE | `/api/admin/google-calendar/disconnect` | admin | Desconecta o Google Calendar |

Com a conexão ativa (`App\Services\GoogleCalendarService`), confirmar um agendamento cria um evento no Google Calendar do admin, cancelar remove esse evento, e os horários já ocupados na agenda pessoal do admin no Google entram como indisponíveis no cálculo de horários livres.

### Pagamento (Stripe)

| Método | Rota | Auth | Descrição |
|---|---|---|---|
| POST | `/api/appointments` | sim | Além de criar o agendamento, já retorna `checkout_url` com a sessão de pagamento do Stripe |
| POST | `/api/stripe/webhook` | não (assinatura verificada via HMAC) | Recebe `checkout.session.completed` (marca o agendamento como pago e dispara e-mail/notificação, ou ativa o plano Pro se for uma assinatura), `checkout.session.expired` (libera o horário se não foi pago), e `customer.subscription.updated`/`.deleted` (sincroniza o plano do negócio com o status da assinatura) |

O agendamento só é considerado reservado de fato depois do pagamento (`payment_status`); se o checkout expirar sem pagamento, `App\Http\Controllers\Api\StripeWebhookController` remove o agendamento pendente pra liberar o horário.

### Planos (Free / Pro)

| Método | Rota | Auth | Descrição |
|---|---|---|---|
| POST | `/api/admin/billing/checkout` | admin | Cria uma sessão de assinatura do Stripe (modo `subscription`) pro plano Pro e retorna `checkout_url` |
| POST | `/api/admin/billing/portal` | admin | Cria uma sessão do portal de cobrança do Stripe (gerenciar forma de pagamento, ver faturas, cancelar), só disponível depois da primeira assinatura |
| POST | `/api/admin/billing/dismiss-premium-prompt` | admin | Registra que o admin viu o aviso de upgrade pro Pro (reaparece depois de 24h se ele continuar no Free) |

O plano fica em `businesses.plan` (`free` por padrão) e é atualizado só via webhook do Stripe, nunca direto pelo cliente. No plano Free os limites são aplicados de verdade no backend, não só escondidos na tela: `App\Http\Requests\StoreServiceRequest` bloqueia cadastrar mais de 1 serviço, e `App\Http\Controllers\Api\AssistantController` aplica uma cota diária de mensagens no assistente por IA (`Business::FREE_ASSISTANT_DAILY_LIMIT`).

### Notificações

| Método | Rota | Auth | Descrição |
|---|---|---|---|
| GET | `/api/notifications` | sim | Lista as últimas notificações do usuário autenticado, com a contagem de não lidas |
| PATCH | `/api/notifications/{notification}/read` | sim | Marca uma notificação como lida |
| POST | `/api/notifications/mark-all-read` | sim | Marca todas as notificações do usuário como lidas |

Usa o sistema nativo de notifications do Laravel (canal `database`), disparada pro cliente quando o pagamento é confirmado, o agendamento é confirmado ou cancelado (os mesmos eventos que disparam e-mail).

### Dashboard de analytics

| Método | Rota | Auth | Descrição |
|---|---|---|---|
| GET | `/api/admin/analytics?days=30` | admin | Resumo de agendamentos no período: por status, por dia, receita (confirmados + concluídos) e serviços mais agendados |

## Regra de conflito de horário

`App\Services\BookingService` calcula os horários livres a partir do horário de atendimento do dia da semana e dos bloqueios cadastrados (ambos configuráveis pelo admin, guardados em `business_hours` e `schedule_blocks`, sempre escopados pelo negócio do serviço) menos os agendamentos ativos, e valida o conflito de novo no momento da criação (dentro de uma transação com lock), pra evitar corrida entre duas requisições simultâneas.
