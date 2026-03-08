# Integração kiosk-app ↔ NexoPOS

## Objetivo
Substituir os dados estáticos do `mock-data.json` do kiosk-app por dados reais vindos da API REST do NexoPOS, e implementar a criação de pedidos reais ao finalizar uma compra no totem.

## Estado Atual

### kiosk-app
- Dados de categorias e produtos são carregados de `/public/mock-data.json` via `fetch`.
- O arquivo `src/services/api.js` existe mas aponta para `https://teu-nexopos.com/api` com token placeholder.
- `checkout()` executa apenas um `alert()` sem integração real.
- Modo de consumo: `eat_in` ou `takeaway` (selecionado na splash).

### NexoPOS
- Sistema Laravel 11 instalado localmente em `http://nexopos.test`.
- API REST protegida por **Laravel Sanctum** (Bearer token).
- Endpoint de categorias para PDV: `GET /api/categories/pos/{id?}`
- Endpoint de criação de pedidos: `POST /api/orders`
- Tipos de pagamento: `GET /api/orders/payments`

---

## Etapas do Plano

### 1. Configurar CORS no NexoPOS

Editar `config/cors.php` para permitir requisições do kiosk-app:
- Em dev: `http://localhost:5173`
- Em produção: IP/domínio do totem

### 2. Criar usuário kiosk no NexoPOS

Criar um usuário dedicado com role de permissões mínimas:
- Leitura de categorias e produtos
- Criação de pedidos

Gerar um **token Sanctum de longa duração** via:
- Admin: `http://nexopos.test/admin/` → Usuários → kiosk → Criar Token
- Ou via `POST /api/users/create-token` (autenticado)

### 3. Configurar `src/services/api.js` no kiosk-app

```js
import axios from 'axios'

const api = axios.create({
  baseURL: import.meta.env.VITE_NEXOPOS_URL + '/api',
  headers: {
    Authorization: 'Bearer ' + import.meta.env.VITE_NEXOPOS_TOKEN
  }
})

export default api
```

Criar `.env` no kiosk-app:
```
VITE_NEXOPOS_URL=http://nexopos.test
VITE_NEXOPOS_TOKEN=<token gerado no passo 2>
```

### 4. Substituir carregamento de dados no App.vue

**Atual:**
```js
const mock = await fetch('/mock-data.json').then(r => r.json())
categories.value = mock.categories
products.value = mock.products
```

**Novo:**
```js
import api from './services/api.js'

// Carregar categorias raiz do PDV
const { data } = await api.get('/categories/pos')
categories.value = data.categories  // [{ id, name, preview_url }]
```

**Ao selecionar uma categoria:**
```js
async function selectCategory(id) {
  selectedCategory.value = id
  const { data } = await api.get(`/categories/pos/${id}`)
  products.value = data.products  // [{ id, name, sale_price, unit_quantities, galleries }]
}
```

**Mapeamento de campos (mock → API real):**

| mock-data.json | API NexoPOS |
|----------------|-------------|
| `id` | `id` |
| `name` | `name` |
| `price` | `sale_price` |
| `category_id` | `category_id` |
| `image` | `galleries[0].url` ou `preview_url` |

### 5. Implementar checkout() real

O pedido precisa do `unit_quantity_id` de cada produto. Este campo vem em `product.unit_quantities[0].id` na resposta da API.

```js
async function checkout() {
  const orderProducts = cartItems.value.map(item => ({
    product_id: item.id,
    unit_quantity_id: item.unit_quantity_id,  // salvar ao adicionar ao carrinho
    quantity: item.quantity,
    unit_price: item.price
  }))

  const total = totalPrice.value

  const { data } = await api.post('/orders', {
    type: mode.value === 'eat_in' ? 'eat_in' : 'takeaway',
    payment_status: 'paid',
    products: orderProducts,
    payments: [
      { identifier: 'cash-payment', value: total }
    ]
  })

  // Exibir confirmação, limpar carrinho
}
```

### 6. Limpar carrinho após pedido

Após `POST /api/orders` bem-sucedido:
- Zerar `quantities`
- Fechar modal do carrinho
- Exibir tela de confirmação com número do pedido
- Retornar ao step 1 (splash) após X segundos

---

## Mapa de Endpoints Utilizados

| Funcionalidade | Método | Endpoint |
|----------------|--------|----------|
| Categorias raiz do PDV | `GET` | `/api/categories/pos` |
| Produtos de uma categoria | `GET` | `/api/categories/pos/{id}` |
| Tipos de pagamento | `GET` | `/api/orders/payments` |
| Criar pedido | `POST` | `/api/orders` |

---

## Pontos de Atenção

- **`unit_quantity_id`**: campo obrigatório no pedido. Todo produto no NexoPOS tem pelo menos uma `unit_quantity`. Ao carregar os produtos via API, salvar o `unit_quantities[0].id` junto com o produto no state do kiosk.
- **`sale_price` vs `price`**: a API retorna `sale_price`, o mock usa `price`. Adaptar o template Vue.
- **Imagens**: a API retorna `galleries` (array de objetos com `url`). Pode ser vazio se nenhuma imagem foi cadastrada. Ter uma imagem fallback.
- **CORS**: sem configuração correta do CORS no NexoPOS, todas as chamadas falharão no browser.
- **Tipo de pedido**: o NexoPOS usa `eat_in` e `takeaway` — compatível com os valores já usados no kiosk-app.
- **Pagamento no totem**: em produção, integrar com terminal de pagamento (MercadoPago já presente no NexoPOS via `POST /api/mercadopago/pay`).

---

## Arquivos a Modificar

### kiosk-app
- `src/services/api.js` — configurar baseURL e token via variáveis de ambiente
- `src/App.vue` — substituir `fetch('/mock-data.json')` por chamadas à API, adaptar `checkout()`
- `.env` (criar) — `VITE_NEXOPOS_URL` e `VITE_NEXOPOS_TOKEN`

### NexoPOS
- `config/cors.php` — permitir origem do kiosk-app
- (Opcional) Criar uma rota pública `/api/kiosk/categories` sem autenticação para simplificar a integração em produção

---

## Próximos Passos (Futuro)

- Integração com pagamento via MercadoPago (`POST /api/mercadopago/pay`)
- Exibição do número do pedido na tela de confirmação
- Notificação em tempo real via **Laravel Reverb** (pedido recebido na cozinha/balcão)
- Tela de inatividade (retorno ao splash após X segundos sem interação)
- Modo offline com fallback local quando a API estiver inacessível
