# SnowSYS — Regras do Projeto

## Visão Geral
Sistema de ponto de venda (PDV) open-source construído em **Laravel 11**. Gerencia produtos, categorias, pedidos, clientes, fornecedores, caixas, relatórios e muito mais. O sistema é o **backend central** para o projeto de totem de auto-atendimento **kiosk-app** (sorveteria Snow Shake).

- **URL local (Herd):** `http://nexopos.test`
- **Admin panel:** `http://nexopos.test/admin/`
- **API base:** `http://nexopos.test/api`

## Tech Stack
- **PHP 8.3** / **Laravel 11**
- **MySQL** (via Laravel Herd, root sem senha, banco `nexopos`)
- **Laravel Sanctum** para autenticação de API (tokens Bearer)
- **Vue 3 + Vite** para o front-end interno do painel
- **TailwindCSS** para estilização do painel
- **Laravel Reverb** para WebSockets (pedidos em tempo real)
- **Laravel Telescope** (desabilitado por padrão: `TELESCOPE_ENABLED=false`)

## Estrutura do Projeto
```
app/
  Http/Controllers/Dashboard/   # Controllers da API e painel
  Models/                        # Eloquent models (Product, Order, Category, etc.)
  Services/                      # Regras de negócio (ProductService, OrderService, etc.)
  Crud/                          # Classes CRUD usadas pelo painel
  Events/ / Listeners/           # Sistema de eventos do Laravel
lang/
  pt.json                        # Tradução PT-BR (principal)
routes/
  api.php                        # Entry point das rotas de API
  api-base.php                   # Rotas protegidas por Sanctum
  api/                           # Rotas agrupadas por recurso
    categories.php
    products.php
    orders.php
    users.php
    ...
  web.php / nexopos.php          # Rotas web do painel
modules/                         # Módulos de extensão do SnowSYS
resources/ts/                    # Código Vue.js do painel interno
planning/                        # Documentação de planejamento e integrações
```

## Endpoints de API Relevantes (kiosk-app)

Todas as rotas abaixo exigem `Authorization: Bearer {token}` exceto onde indicado.

| Método | Endpoint | Descrição |
|--------|----------|-----------|
| `GET` | `/api/categories/pos` | Lista categorias raiz habilitadas no PDV |
| `GET` | `/api/categories/pos/{id}` | Retorna produtos + subcategorias de uma categoria |
| `GET` | `/api/products` | Lista todos os produtos |
| `GET` | `/api/orders/payments` | Lista os tipos de pagamento disponíveis |
| `POST` | `/api/orders` | Cria um pedido novo |
| `POST` | `/api/users/create-token` | Cria token Sanctum para um usuário autenticado |

### Formato de resposta: `GET /api/categories/pos/{id}`
```json
{
  "categories": [{ "id": 1, "name": "Cascão", "preview_url": "..." }],
  "products": [{ "id": 1, "name": "Soft Chocolate", "sale_price": 8.0, "galleries": [], "unit_quantities": [] }],
  "previousCategory": null,
  "currentCategory": { "id": 1, "name": "Cascão" }
}
```

### Formato de pedido: `POST /api/orders`
```json
{
  "type": "takeaway",
  "payment_status": "paid",
  "products": [
    {
      "product_id": 1,
      "unit_quantity_id": 2,
      "quantity": 1,
      "unit_price": 8.00
    }
  ],
  "payments": [
    { "identifier": "cash-payment", "value": 8.00 }
  ]
}
```

## Autenticação (Sanctum)

- Tokens são gerados via `POST /api/users/create-token` (requer sessão autenticada).
- Para o kiosk, criar um usuário dedicado com permissões mínimas de leitura de produtos e criação de pedidos.
- O token deve ser armazenado no `.env` do kiosk-app e nunca exposto no frontend em produção.

## Convenções de Código

- **Sempre usar Eloquent ORM** em vez de Query Builder, pois garante o disparo de observers.
- **Nunca usar `--force`** ao rodar migrations.
- **Admin panel** acessado via `/admin/` (não `/nova/`).
- **Logs de webhooks** devem usar prefixos: `[WEBHOOK][STRIPE]`, `[WEBHOOK][MAGALU]`, `[WEBHOOK][MercadoLivre]`.
- **Roles e permissões:** `admin`, `atendimento` e `gerente` acessam apenas dados da própria empresa. `superadmin` acessa tudo.
- **Dashboard:** apenas `superadmin` e `gerente` têm acesso.
- **Cores:** não usar gradientes; manter o esquema padrão do Laravel Nova.

## CORS (Integração com kiosk-app)

Para que o kiosk-app (rodando em `http://localhost:5173` em dev) consuma a API do SnowSYS, configurar em `config/cors.php`:
- `allowed_origins`: incluir a URL do kiosk-app
- `allowed_headers`: `['*']`
- `supports_credentials`: `false` (kiosk usa token, não cookie)

## Projeto Relacionado

| Projeto | Caminho | Descrição |
|---------|---------|-----------|
| **kiosk-app** | `../kiosk-app` | Totem Vue 3 de auto-atendimento da Snow Shake |

Ver `planning/kiosk-nexopos-integration.md` para o plano de integração.

## Comandos Úteis
```bash
php artisan migrate                  # Rodar migrations (nunca --force)
php artisan db:seed                  # Popular banco com dados de exemplo
php artisan ns:translate --symlink   # Sincronizar traduções
php artisan cache:clear              # Limpar cache
php artisan optimize:clear           # Limpar tudo (cache, config, routes, views)
npm run dev                          # Servidor Vite para o painel
npm run build                        # Build de produção dos assets
```
