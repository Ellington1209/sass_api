# 📋 CRUD Completo - Services (Serviços)

Documentação completa das rotas e exemplos de JSON para o módulo de Serviços.

---

## 🔗 Rotas Disponíveis

### Base URL
```
/api/agenda/services
```

### Autenticação
Todas as rotas requerem autenticação via Bearer Token:
```
Authorization: Bearer SEU_TOKEN_AQUI
```

---

## 📖 1. LISTAR SERVIÇOS (GET)

### Rota
```
GET /api/agenda/services
```

### Permissão
`agenda.services.view`

### Query Parameters (Opcionais)
- `active` - Filtrar por serviços ativos (true/false)


```

### Resposta de Sucesso (200)
```json
[
  {
    "id": 1,
    "tenant_id": 1,
    "module_id": 10,
    "name": "Aulas Práticas",
    "slug": "aulas-praticas",
    "duration_minutes": 50,
    "active": true,
    "module": {
      "id": 10,
      "key": "auto-escola",
      "name": "Auto Escola"
    },
    "price": {
      "id": 1,
      "price": 150.00,
      "currency": "BRL",
      "start_date": "2025-01-01",
      "end_date": null
    },
    "created_at": "2025-12-03T10:00:00.000000Z",
    "updated_at": "2025-12-03T10:00:00.000000Z"
  },
  {
    "id": 2,
    "tenant_id": 1,
    "module_id": 10,
    "name": "Aulas Teóricas",
    "slug": "aulas-teoricas",
    "duration_minutes": 50,
    "active": true,
    "module": {
      "id": 10,
      "key": "auto-escola",
      "name": "Auto Escola"
    },
    "price": null,
    "created_at": "2025-12-03T10:00:00.000000Z",
    "updated_at": "2025-12-03T10:00:00.000000Z"
  }
]
```

**Observação:** Apenas serviços dos módulos ativos do tenant (em `tenant_modules`) são retornados.

---

## 🔍 2. BUSCAR SERVIÇO POR ID (GET)

### Rota
```
GET /api/agenda/services/{id}
```

### Permissão
`agenda.services.view`

### Parâmetros
- `id` - ID do serviço (path parameter)

### Exemplo de Requisição (cURL)
```bash
curl -X GET "http://localhost:8080/api/agenda/services/1" \
  -H "Authorization: Bearer SEU_TOKEN" \
  -H "Content-Type: application/json"
```

### Exemplo de Requisição (JavaScript/Fetch)
```javascript
const response = await fetch('http://localhost:8080/api/agenda/services/1', {
  method: 'GET',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json'
  }
});

const service = await response.json();
console.log(service);
```

### Resposta de Sucesso (200)
```json
{
  "id": 1,
  "tenant_id": 1,
  "module_id": 10,
  "name": "Aulas Práticas",
  "slug": "aulas-praticas",
  "duration_minutes": 50,
  "active": true,
  "module": {
    "id": 10,
    "key": "auto-escola",
    "name": "Auto Escola"
  },
  "price": {
    "id": 1,
    "price": 150.00,
    "currency": "BRL",
    "start_date": "2025-01-01",
    "end_date": null
  },
  "created_at": "2025-12-03T10:00:00.000000Z",
  "updated_at": "2025-12-03T10:00:00.000000Z"
}
```

### Resposta de Erro (404)
```json
{
  "message": "Serviço não encontrado"
}
```

---

## ➕ 3. CRIAR SERVIÇO (POST)

### Rota
```
POST /api/agenda/services
```

### Permissão
`agenda.services.create`

### Payload JSON
```json
{
  "module_id": 10,
  "name": "Aulas Práticas",
  "slug": "aulas-praticas",
  "duration_minutes": 50,
  "active": true,
  "price": 150.00,
  "currency": "BRL",
  "price_active": true,
  "price_start_date": "2025-01-01",
  "price_end_date": null
}
```

### Validação
- `module_id` - **obrigatório**, integer, deve existir em `modules` e estar ativo para o tenant
- `name` - **obrigatório**, string, máximo 255 caracteres
- `slug` - **obrigatório**, string, máximo 255 caracteres
- `duration_minutes` - **obrigatório**, integer, mínimo 1
- `active` - opcional, boolean (padrão: true)
- `price` - opcional, numeric, mínimo 0 (cria preço para o serviço)
- `currency` - opcional, string, tamanho 3 (padrão: "BRL")
- `price_active` - opcional, boolean (padrão: true)
- `price_start_date` - opcional, date (data de início da vigência do preço)
- `price_end_date` - opcional, date, deve ser maior ou igual a `price_start_date` (data de fim da vigência)

### Exemplo de Requisição (cURL)
```bash
curl -X POST "http://localhost:8080/api/agenda/services" \
  -H "Authorization: Bearer SEU_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "module_id": 10,
    "name": "Aulas Práticas",
    "slug": "aulas-praticas",
    "duration_minutes": 50,
    "active": true,
    "price": 150.00,
    "currency": "BRL"
  }'
```

### Exemplo de Requisição (JavaScript/Fetch)
```javascript
const response = await fetch('http://localhost:8080/api/agenda/services', {
  method: 'POST',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    module_id: 10,
    name: "Aulas Práticas",
    slug: "aulas-praticas",
    duration_minutes: 50,
    active: true,
    price: 150.00,
    currency: "BRL"
  })
});

const service = await response.json();
console.log(service);
```

### Exemplo de Requisição (Axios)
```javascript
import axios from 'axios';

const service = await axios.post('/api/agenda/services', {
  module_id: 10,
  name: "Aulas Práticas",
  slug: "aulas-praticas",
  duration_minutes: 50,
  active: true,
  price: 150.00,
  currency: "BRL"
}, {
  headers: {
    'Authorization': `Bearer ${token}`
  }
});

console.log(service.data);
```

### Resposta de Sucesso (201)
```json
{
  "id": 1,
  "tenant_id": 1,
  "module_id": 10,
  "name": "Aulas Práticas",
  "slug": "aulas-praticas",
  "duration_minutes": 50,
  "active": true,
  "module": {
    "id": 10,
    "key": "auto-escola",
    "name": "Auto Escola"
  },
  "price": {
    "id": 1,
    "price": 150.00,
    "currency": "BRL",
    "start_date": null,
    "end_date": null
  },
  "created_at": "2025-12-03T10:00:00.000000Z",
  "updated_at": "2025-12-03T10:00:00.000000Z"
}
```

### Resposta de Erro (422) - Validação
```json
{
  "message": "Erro na validação",
  "errors": {
    "module_id": ["O campo module_id é obrigatório."],
    "name": ["O campo name é obrigatório."],
    "duration_minutes": ["O campo duration_minutes deve ser no mínimo 1."]
  }
}
```

### Resposta de Erro (422) - Módulo não ativo
```json
{
  "message": "Módulo não está ativo para este tenant"
}
```

---

## ✏️ 4. ATUALIZAR SERVIÇO (PUT/PATCH)

### Rotas
```
PUT /api/agenda/services/{id}
PATCH /api/agenda/services/{id}
```

### Permissão
`agenda.services.edit`

### Parâmetros
- `id` - ID do serviço (path parameter)

### Payload JSON (todos os campos são opcionais)
```json
{
  "module_id": 11,
  "name": "Corte Masculino",
  "slug": "corte-masculino",
  "duration_minutes": 30,
  "active": false,
  "price": 25.00,
  "currency": "BRL",
  "price_active": true,
  "update_price": true
}
```

### Validação
- `module_id` - opcional, integer, deve existir em `modules` e estar ativo para o tenant
- `name` - opcional, string, máximo 255 caracteres
- `slug` - opcional, string, máximo 255 caracteres
- `duration_minutes` - opcional, integer, mínimo 1
- `active` - opcional, boolean
- `price` - opcional, numeric, mínimo 0 (cria novo preço ou atualiza se `update_price=true`)
- `currency` - opcional, string, tamanho 3 (padrão: "BRL")
- `price_active` - opcional, boolean (padrão: true)
- `price_start_date` - opcional, date
- `price_end_date` - opcional, date, deve ser maior ou igual a `price_start_date`
- `update_price` - opcional, boolean (se true, desativa preços antigos e cria novo)

**Observação:** Se `update_price=true`, todos os preços ativos anteriores do serviço serão desativados e um novo preço será criado.

### Exemplo de Requisição (cURL)
```bash
curl -X PUT "http://localhost:8080/api/agenda/services/1" \
  -H "Authorization: Bearer SEU_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Aulas Práticas Atualizado",
    "duration_minutes": 60,
    "active": true
  }'
```

### Exemplo de Requisição (JavaScript/Fetch)
```javascript
const response = await fetch('http://localhost:8080/api/agenda/services/1', {
  method: 'PUT',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    name: "Aulas Práticas Atualizado",
    duration_minutes: 60,
    active: true
  })
});

const service = await response.json();
console.log(service);
```

### Exemplo de Requisição (Axios)
```javascript
import axios from 'axios';

const service = await axios.put('/api/agenda/services/1', {
  name: "Aulas Práticas Atualizado",
  duration_minutes: 60,
  active: true
}, {
  headers: {
    'Authorization': `Bearer ${token}`
  }
});

console.log(service.data);
```

### Resposta de Sucesso (200)
```json
{
  "id": 1,
  "tenant_id": 1,
  "module_id": 10,
  "name": "Aulas Práticas Atualizado",
  "slug": "aulas-praticas",
  "duration_minutes": 60,
  "active": true,
  "module": {
    "id": 10,
    "key": "auto-escola",
    "name": "Auto Escola"
  },
  "price": {
    "id": 2,
    "price": 180.00,
    "currency": "BRL",
    "start_date": null,
    "end_date": null
  },
  "created_at": "2025-12-03T10:00:00.000000Z",
  "updated_at": "2025-12-03T10:30:00.000000Z"
}
```

### Resposta de Erro (404)
```json
{
  "message": "Serviço não encontrado"
}
```

### Resposta de Erro (422) - Módulo não ativo
```json
{
  "message": "Módulo não está ativo para este tenant"
}
```

---

## 🗑️ 5. EXCLUIR SERVIÇO (DELETE)

### Rotas Disponíveis
```
DELETE /api/agenda/services/{id}
DELETE /api/agenda/services/batch
DELETE /api/agenda/services
```

### Permissão
`agenda.services.delete`

### Opção 1: Excluir por ID na URL

#### Rota
```
DELETE /api/agenda/services/{id}
```

#### Parâmetros
- `id` - ID do serviço (path parameter)

#### Exemplo de Requisição (cURL)
```bash
curl -X DELETE "http://localhost:8080/api/agenda/services/1" \
  -H "Authorization: Bearer SEU_TOKEN" \
  -H "Content-Type: application/json"
```

#### Exemplo de Requisição (JavaScript/Fetch)
```javascript
const response = await fetch('http://localhost:8080/api/agenda/services/1', {
  method: 'DELETE',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json'
  }
});

const result = await response.json();
console.log(result);
```

#### Resposta de Sucesso (200)
```json
{
  "message": "Serviço excluído com sucesso",
  "deleted": [1]
}
```

---

### Opção 2: Excluir múltiplos (Batch) - Array no Body

#### Rota
```
DELETE /api/agenda/services/batch
DELETE /api/agenda/services
```

#### Payload JSON
```json
{
  "ids": [1, 2, 3]
}
```

#### Exemplo de Requisição (cURL)
```bash
curl -X DELETE "http://localhost:8080/api/agenda/services/batch" \
  -H "Authorization: Bearer SEU_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "ids": [1, 2, 3]
  }'
```

#### Exemplo de Requisição (JavaScript/Fetch)
```javascript
const response = await fetch('http://localhost:8080/api/agenda/services/batch', {
  method: 'DELETE',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    ids: [1, 2, 3]
  })
});

const result = await response.json();
console.log(result);
```

#### Exemplo de Requisição (Axios)
```javascript
import axios from 'axios';

const result = await axios.delete('/api/agenda/services/batch', {
  data: { ids: [1, 2, 3] },
  headers: {
    'Authorization': `Bearer ${token}`
  }
});

console.log(result.data);
```

#### Resposta de Sucesso (200)
```json
{
  "message": "3 serviços excluídos com sucesso",
  "deleted": [1, 2, 3]
}
```

#### Resposta Parcial (200) - Alguns não encontrados
```json
{
  "message": "1 serviço excluído com sucesso",
  "deleted": [1],
  "not_found": [2, 3]
}
```

#### Resposta de Erro (404) - Nenhum encontrado
```json
{
  "message": "Nenhum serviço encontrado",
  "not_found": [1, 2, 3]
}
```

---

## 📝 Exemplos de Payloads por Módulo

### Auto-escola (module_id: 10)
```json
{
  "module_id": 10,
  "name": "Aulas Práticas",
  "slug": "aulas-praticas",
  "duration_minutes": 50,
  "active": true,
  "price": 150.00,
  "currency": "BRL"
}
```

```json
{
  "module_id": 10,
  "name": "Aulas Teóricas",
  "slug": "aulas-teoricas",
  "duration_minutes": 50,
  "active": true,
  "price": 120.00,
  "currency": "BRL"
}
```

### Barbearia (module_id: 11)
```json
{
  "module_id": 11,
  "name": "Corte Masculino",
  "slug": "corte-masculino",
  "duration_minutes": 30,
  "active": true,
  "price": 25.00,
  "currency": "BRL"
}
```

```json
{
  "module_id": 11,
  "name": "Barba",
  "slug": "barba",
  "duration_minutes": 20,
  "active": true,
  "price": 15.00,
  "currency": "BRL"
}
```

```json
{
  "module_id": 11,
  "name": "Corte + Barba",
  "slug": "corte-barba",
  "duration_minutes": 45,
  "active": true,
  "price": 35.00,
  "currency": "BRL"
}
```

### Salão de Beleza (module_id: 12)
```json
{
  "module_id": 12,
  "name": "Corte Feminino",
  "slug": "corte-feminino",
  "duration_minutes": 45,
  "active": true,
  "price": 50.00,
  "currency": "BRL"
}
```

```json
{
  "module_id": 12,
  "name": "Manicure",
  "slug": "manicure",
  "duration_minutes": 45,
  "active": true,
  "price": 30.00,
  "currency": "BRL"
}
```

```json
{
  "module_id": 12,
  "name": "Coloração",
  "slug": "coloracao",
  "duration_minutes": 120,
  "active": true,
  "price": 150.00,
  "currency": "BRL"
}
```

---

## 🚨 Tratamento de Erros

### Erro 400 - Bad Request
```json
{
  "message": "Tenant não identificado"
}
```

### Erro 404 - Not Found
```json
{
  "message": "Serviço não encontrado"
}
```

### Erro 422 - Unprocessable Entity

**Validação:**
```json
{
  "message": "Erro na validação",
  "errors": {
    "module_id": ["O campo module_id é obrigatório."],
    "name": ["O campo name é obrigatório."],
    "slug": ["O campo slug é obrigatório."],
    "duration_minutes": ["O campo duration_minutes deve ser no mínimo 1."]
  }
}
```

**Módulo não ativo:**
```json
{
  "message": "Módulo não está ativo para este tenant"
}
```

---

## ⚠️ Observações Importantes

1. **Module ID:** Obrigatório ao criar. Deve estar nos módulos ativos do tenant em `tenant_modules`
2. **Filtro Automático:** Apenas serviços dos módulos ativos do tenant são retornados
3. **Slug:** Deve ser único por tenant e módulo
4. **Duration Minutes:** Deve ser no mínimo 1 minuto
5. **Super Admin:** Ignora filtro de tenant e vê todos os serviços
6. **Preço:** Opcional ao criar/atualizar. Se não informado, o serviço ficará sem preço
7. **Histórico de Preços:** Cada vez que você atualiza o preço com `update_price=true`, os preços antigos são desativados e um novo é criado (mantém histórico)
8. **Vigência de Preço:** Use `price_start_date` e `price_end_date` para definir períodos de promoção ou variações de preço
9. **Preço Ativo:** O sistema retorna apenas o preço ativo e dentro da vigência (se houver datas)

---

## 📋 Resumo das Rotas

| Método | Rota | Permissão | Descrição |
|--------|------|-----------|-----------|
| GET | `/api/agenda/services` | `agenda.services.view` | Listar serviços |
| GET | `/api/agenda/services/{id}` | `agenda.services.view` | Buscar serviço por ID |
| POST | `/api/agenda/services` | `agenda.services.create` | Criar serviço |
| PUT | `/api/agenda/services/{id}` | `agenda.services.edit` | Atualizar serviço |
| PATCH | `/api/agenda/services/{id}` | `agenda.services.edit` | Atualizar serviço |
| DELETE | `/api/agenda/services/{id}` | `agenda.services.delete` | Excluir serviço |
| DELETE | `/api/agenda/services/batch` | `agenda.services.delete` | Excluir múltiplos |
| DELETE | `/api/agenda/services` | `agenda.services.delete` | Excluir múltiplos |

---

**Documentação atualizada em:** 2025-12-03

