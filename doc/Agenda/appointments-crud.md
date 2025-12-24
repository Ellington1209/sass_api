# 📋 CRUD Completo - Appointments (Agendamentos)

Documentação completa das rotas e exemplos de JSON para o módulo de Agendamentos.

---

## 🔗 Rotas Disponíveis

### Base URL
```
/api/agenda/appointments
```

### Autenticação
Todas as rotas requerem autenticação via Bearer Token:
```
Authorization: Bearer SEU_TOKEN_AQUI
```

---

## 📖 1. LISTAR AGENDAMENTOS (GET)

### Rota
```
GET /api/agenda/appointments
```

### Permissão
`agenda.appointments.view`

### Query Parameters (Opcionais)
- `provider_id` - Filtrar por profissional (integer)
- `date_start` - Filtrar a partir desta data (datetime, formato: YYYY-MM-DD ou YYYY-MM-DD HH:mm:ss)
- `date_end` - Filtrar até esta data (datetime, formato: YYYY-MM-DD ou YYYY-MM-DD HH:mm:ss)

### Exemplo de Requisição (cURL)
```bash
curl -X GET "http://localhost:8080/api/agenda/appointments?provider_id=2&date_start=2025-12-15" \
  -H "Authorization: Bearer SEU_TOKEN" \
  -H "Content-Type: application/json"
```

### Exemplo de Requisição (JavaScript/Fetch)
```javascript
const response = await fetch('http://localhost:8080/api/agenda/appointments?provider_id=2&date_start=2025-12-15', {
  method: 'GET',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json'
  }
});

const appointments = await response.json();
console.log(appointments);
```

### Resposta de Sucesso (200)
```json
[
  {
    "id": 1,
    "tenant_id": 1,
    "service_id": 1,
    "provider_id": 2,
    "client_id": 3,
    "date_start": "2025-12-15T14:30:00.000000Z",
    "date_end": "2025-12-15T15:00:00.000000Z",
    "status_agenda_id": 1,
    "notes": "Cliente prefere horário da manhã",
    "service": {
      "id": 1,
      "name": "Aula Prática",
      "slug": "aula-pratica",
      "duration_minutes": 30
    },
    "provider": {
      "id": 2,
      "person_id": 5,
      "user": {
        "id": 5,
        "name": "João Silva",
        "email": "joao@example.com"
      },
      "person": {
        "id": 5,
        "cpf": "123.456.789-00",
        "rg": "1234567",
        "birth_date": "1990-05-15",
        "phone": "(62) 99999-9999"
      },
      "service_ids": [1, 2, 3]
    },
    "client": {
      "id": 3,
      "name": "Maria Santos",
      "email": "maria@example.com"
    },
    "status_agenda": {
      "id": 1,
      "key": "agendado",
      "name": "Agendado"
    },
    "created_at": "2025-12-03T10:00:00.000000Z",
    "updated_at": "2025-12-03T10:00:00.000000Z"
  }
]
```

**Observação:** Os agendamentos são ordenados por `date_start` (mais antigos primeiro).

---

## 🔍 2. BUSCAR AGENDAMENTO POR ID (GET)

### Rota
```
GET /api/agenda/appointments/{id}
```

### Permissão
`agenda.appointments.view`

### Parâmetros
- `id` - ID do agendamento (path parameter)

### Exemplo de Requisição (cURL)
```bash
curl -X GET "http://localhost:8080/api/agenda/appointments/1" \
  -H "Authorization: Bearer SEU_TOKEN" \
  -H "Content-Type: application/json"
```

### Exemplo de Requisição (JavaScript/Fetch)
```javascript
const response = await fetch('http://localhost:8080/api/agenda/appointments/1', {
  method: 'GET',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json'
  }
});

const appointment = await response.json();
console.log(appointment);
```

### Resposta de Sucesso (200)
```json
{
  "id": 1,
  "tenant_id": 1,
  "service_id": 1,
  "provider_id": 2,
  "client_id": 3,
  "date_start": "2025-12-15T14:30:00.000000Z",
  "date_end": "2025-12-15T15:00:00.000000Z",
  "status_agenda_id": 1,
  "notes": "Cliente prefere horário da manhã",
  "service": {
    "id": 1,
    "name": "Aula Prática",
    "slug": "aula-pratica",
    "duration_minutes": 30
  },
  "provider": {
    "id": 2,
    "person_id": 5,
    "user": {
      "id": 5,
      "name": "João Silva",
      "email": "joao@example.com"
    },
    "person": {
      "id": 5,
      "cpf": "123.456.789-00",
      "rg": "1234567",
      "birth_date": "1990-05-15",
      "phone": "(62) 99999-9999"
    },
    "service_ids": [1, 2, 3]
  },
  "client": {
    "id": 3,
    "name": "Maria Santos",
    "email": "maria@example.com"
  },
  "status_agenda": {
    "id": 1,
    "key": "agendado",
    "name": "Agendado"
  },
  "created_at": "2025-12-03T10:00:00.000000Z",
  "updated_at": "2025-12-03T10:00:00.000000Z"
}
```

### Resposta de Erro (404)
```json
{
  "message": "Agendamento não encontrado"
}
```

---

## ➕ 3. CRIAR AGENDAMENTO (POST)

### Rota
```
POST /api/agenda/appointments
```

### Permissão
`agenda.appointments.create`

### Payload JSON
```json
{
  "service_id": 1,
  "provider_id": 2,
  "client_id": 3,
  "date_start": "2025-12-15 14:30:00",
  "status_agenda_id": 1,
  "notes": "Cliente prefere horário da manhã"
}
```

### Validação
- `service_id` - **obrigatório**, integer, deve existir em `services`
- `provider_id` - **obrigatório**, integer, deve existir em `providers`
- `client_id` - **obrigatório**, integer, deve existir em `users`
- `date_start` - **obrigatório**, datetime válido (formato: YYYY-MM-DD HH:mm:ss ou ISO 8601)
- `status_agenda_id` - opcional, integer, deve existir em `status_agenda`
- `notes` - opcional, string

### Regras Automáticas
1. **Cálculo de `date_end`**: O campo `date_end` é calculado automaticamente somando `duration_minutes` do serviço ao `date_start`
2. **Tenant ID**: O `tenant_id` é preenchido automaticamente pelo sistema
3. **Super Admin**: Super admin não pode criar agendamentos (retorna erro 403)
4. **Hierarquia de Validação (REGRA MESTRE):** A ordem de validação NÃO pode ser invertida:
   - **1º:** Horário do Tenant (nada pode acontecer fora) → "Fora do horário de funcionamento do estabelecimento"
   - **2º:** Horário do Profissional (sempre dentro do tenant) → "Fora do horário de disponibilidade do profissional"
   - **3º:** Bloqueios (folgas, almoços, etc.) → "Horário bloqueado"
   - **4º:** Conflitos com outros agendamentos → "Conflito de horário detectado"

### Exemplo de Requisição (cURL)
```bash
curl -X POST "http://localhost:8080/api/agenda/appointments" \
  -H "Authorization: Bearer SEU_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "service_id": 1,
    "provider_id": 2,
    "client_id": 3,
    "date_start": "2025-12-15 14:30:00",
    "status_agenda_id": 1,
    "notes": "Cliente prefere horário da manhã"
  }'
```

### Exemplo de Requisição (JavaScript/Fetch)
```javascript
const response = await fetch('http://localhost:8080/api/agenda/appointments', {
  method: 'POST',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    service_id: 1,
    provider_id: 2,
    client_id: 3,
    date_start: "2025-12-15 14:30:00",
    status_agenda_id: 1,
    notes: "Cliente prefere horário da manhã"
  })
});

const appointment = await response.json();
console.log(appointment);
```

### Exemplo de Requisição (Axios)
```javascript
import axios from 'axios';

const appointment = await axios.post('/api/agenda/appointments', {
  service_id: 1,
  provider_id: 2,
  client_id: 3,
  date_start: "2025-12-15 14:30:00",
  status_agenda_id: 1,
  notes: "Cliente prefere horário da manhã"
}, {
  headers: {
    'Authorization': `Bearer ${token}`
  }
});

console.log(appointment.data);
```

### Resposta de Sucesso (201)
```json
{
  "id": 1,
  "tenant_id": 1,
  "service_id": 1,
  "provider_id": 2,
  "client_id": 3,
  "date_start": "2025-12-15T14:30:00.000000Z",
  "date_end": "2025-12-15T15:00:00.000000Z",
  "status_agenda_id": 1,
  "notes": "Cliente prefere horário da manhã",
  "service": {
    "id": 1,
    "name": "Aula Prática",
    "slug": "aula-pratica",
    "duration_minutes": 30
  },
  "provider": {
    "id": 2,
    "person_id": 5,
    "user": {
      "id": 5,
      "name": "João Silva",
      "email": "joao@example.com"
    },
    "person": {
      "id": 5,
      "cpf": "123.456.789-00",
      "rg": "1234567",
      "birth_date": "1990-05-15",
      "phone": "(62) 99999-9999"
    },
    "service_ids": [1, 2, 3]
  },
  "client": {
    "id": 3,
    "name": "Maria Santos",
    "email": "maria@example.com"
  },
  "status_agenda": {
    "id": 1,
    "key": "agendado",
    "name": "Agendado"
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
    "service_id": ["O campo service_id é obrigatório."],
    "provider_id": ["O campo provider_id é obrigatório."],
    "client_id": ["O campo client_id é obrigatório."],
    "date_start": ["O campo date_start deve ser uma data válida."]
  }
}
```

### Resposta de Erro (422) - Conflito de Horário
```json
{
  "message": "Conflito de horário detectado"
}
```

### Resposta de Erro (403) - Super Admin
```json
{
  "message": "Super admin não pode criar agendamentos"
}
```

### Resposta de Erro (400) - Tenant Não Identificado
```json
{
  "message": "Tenant não identificado"
}
```

---

## ✏️ 4. ATUALIZAR AGENDAMENTO (PUT/PATCH)

### Rotas
```
PUT /api/agenda/appointments/{id}
PATCH /api/agenda/appointments/{id}
```

### Permissão
`agenda.appointments.edit`

### Parâmetros
- `id` - ID do agendamento (path parameter)

### Payload JSON (todos os campos são opcionais)
```json
{
  "service_id": 2,
  "provider_id": 3,
  "client_id": 4,
  "date_start": "2025-12-16 10:00:00",
  "status_agenda_id": 2,
  "notes": "Observações atualizadas"
}
```

### Validação
- `service_id` - opcional, integer, deve existir em `services`
- `provider_id` - opcional, integer, deve existir em `providers`
- `client_id` - opcional, integer, deve existir em `users`
- `date_start` - opcional, datetime válido
- `status_agenda_id` - opcional, integer, deve existir em `status_agenda` (pode ser `null`)
- `notes` - opcional, string (pode ser `null`)

### Regras Automáticas
1. **Recálculo de `date_end`**: Se `service_id` ou `date_start` for alterado, o `date_end` é recalculado automaticamente
2. **Super Admin**: Super admin não pode atualizar agendamentos (retorna erro 403)
3. **Hierarquia de Validação (REGRA MESTRE):** A ordem de validação NÃO pode ser invertida:
   - **1º:** Horário do Tenant (nada pode acontecer fora) → "Fora do horário de funcionamento do estabelecimento"
   - **2º:** Horário do Profissional (sempre dentro do tenant) → "Fora do horário de disponibilidade do profissional"
   - **3º:** Bloqueios (folgas, almoços, etc.) → "Horário bloqueado"
   - **4º:** Conflitos com outros agendamentos (excluindo o próprio) → "Conflito de horário detectado"

### Exemplo de Requisição (cURL)
```bash
curl -X PUT "http://localhost:8080/api/agenda/appointments/1" \
  -H "Authorization: Bearer SEU_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "date_start": "2025-12-16 10:00:00",
    "status_agenda_id": 2,
    "notes": "Horário alterado para manhã"
  }'
```

### Exemplo de Requisição (JavaScript/Fetch)
```javascript
const response = await fetch('http://localhost:8080/api/agenda/appointments/1', {
  method: 'PUT',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    date_start: "2025-12-16 10:00:00",
    status_agenda_id: 2,
    notes: "Horário alterado para manhã"
  })
});

const appointment = await response.json();
console.log(appointment);
```

### Exemplo de Requisição (Axios)
```javascript
import axios from 'axios';

const appointment = await axios.put('/api/agenda/appointments/1', {
  date_start: "2025-12-16 10:00:00",
  status_agenda_id: 2,
  notes: "Horário alterado para manhã"
}, {
  headers: {
    'Authorization': `Bearer ${token}`
  }
});

console.log(appointment.data);
```

### Resposta de Sucesso (200)
```json
{
  "id": 1,
  "tenant_id": 1,
  "service_id": 1,
  "provider_id": 2,
  "client_id": 3,
  "date_start": "2025-12-16T10:00:00.000000Z",
  "date_end": "2025-12-16T10:30:00.000000Z",
  "status_agenda_id": 2,
  "notes": "Horário alterado para manhã",
  "service": {
    "id": 1,
    "name": "Aula Prática",
    "slug": "aula-pratica",
    "duration_minutes": 30
  },
  "provider": {
    "id": 2,
    "person_id": 5,
    "user": {
      "id": 5,
      "name": "João Silva",
      "email": "joao@example.com"
    },
    "person": {
      "id": 5,
      "cpf": "123.456.789-00",
      "rg": "1234567",
      "birth_date": "1990-05-15",
      "phone": "(62) 99999-9999"
    },
    "service_ids": [1, 2, 3]
  },
  "client": {
    "id": 3,
    "name": "Maria Santos",
    "email": "maria@example.com"
  },
  "status_agenda": {
    "id": 2,
    "key": "confirmado",
    "name": "Confirmado"
  },
  "created_at": "2025-12-03T10:00:00.000000Z",
  "updated_at": "2025-12-16T08:00:00.000000Z"
}
```

### Resposta de Erro (404)
```json
{
  "message": "Agendamento não encontrado"
}
```

### Resposta de Erro (422) - Conflito de Horário
```json
{
  "message": "Conflito de horário detectado"
}
```

### Resposta de Erro (403) - Super Admin
```json
{
  "message": "Super admin não pode atualizar agendamentos"
}
```

---

## 🗑️ 5. EXCLUIR AGENDAMENTO (DELETE)

### Rotas Disponíveis
```
DELETE /api/agenda/appointments/{id}
DELETE /api/agenda/appointments/batch
DELETE /api/agenda/appointments
```

### Permissão
`agenda.appointments.delete`

### Opção 1: Excluir por ID na URL

#### Rota
```
DELETE /api/agenda/appointments/{id}
```

#### Parâmetros
- `id` - ID do agendamento (path parameter)

#### Exemplo de Requisição (cURL)
```bash
curl -X DELETE "http://localhost:8080/api/agenda/appointments/1" \
  -H "Authorization: Bearer SEU_TOKEN" \
  -H "Content-Type: application/json"
```

#### Exemplo de Requisição (JavaScript/Fetch)
```javascript
const response = await fetch('http://localhost:8080/api/agenda/appointments/1', {
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
  "message": "Agendamento excluído com sucesso",
  "deleted": [1]
}
```

---

### Opção 2: Excluir múltiplos (Batch) - Array no Body

#### Rota
```
DELETE /api/agenda/appointments/batch
DELETE /api/agenda/appointments
```

#### Payload JSON
```json
{
  "ids": [1, 2, 3]
}
```

#### Exemplo de Requisição (cURL)
```bash
curl -X DELETE "http://localhost:8080/api/agenda/appointments/batch" \
  -H "Authorization: Bearer SEU_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "ids": [1, 2, 3]
  }'
```

#### Exemplo de Requisição (JavaScript/Fetch)
```javascript
const response = await fetch('http://localhost:8080/api/agenda/appointments/batch', {
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

const result = await axios.delete('/api/agenda/appointments/batch', {
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
  "message": "3 agendamentos excluídos com sucesso",
  "deleted": [1, 2, 3]
}
```

#### Resposta Parcial (200) - Alguns não encontrados
```json
{
  "message": "1 agendamento excluído com sucesso",
  "deleted": [1],
  "not_found": [2, 3]
}
```

#### Resposta de Erro (404) - Nenhum encontrado
```json
{
  "message": "Nenhum agendamento encontrado",
  "not_found": [1, 2, 3]
}
```

#### Resposta de Erro (403) - Super Admin
```json
{
  "message": "Super admin não pode excluir agendamentos"
}
```

---

## 📝 Exemplos de Payloads por Cenário

### Criar Agendamento - Auto-escola
```json
{
  "service_id": 1,
  "provider_id": 2,
  "client_id": 3,
  "date_start": "2025-12-15 14:30:00",
  "status_agenda_id": 1,
  "notes": "Aluno precisa de aula prática para exame"
}
```

### Criar Agendamento - Barbearia
```json
{
  "service_id": 5,
  "provider_id": 3,
  "client_id": 4,
  "date_start": "2025-12-16 09:00:00",
  "status_agenda_id": 1,
  "notes": "Cliente prefere corte tradicional"
}
```

### Atualizar Status do Agendamento
```json
{
  "status_agenda_id": 3
}
```

### Atualizar Horário do Agendamento
```json
{
  "date_start": "2025-12-16 15:00:00"
}
```

### Atualizar Profissional do Agendamento
```json
{
  "provider_id": 4
}
```

### Atualizar Múltiplos Campos
```json
{
  "provider_id": 5,
  "date_start": "2025-12-17 10:00:00",
  "status_agenda_id": 2,
  "notes": "Agendamento remarcado"
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

```json
{
  "message": "ID ou array de IDs não fornecido"
}
```

### Erro 403 - Forbidden
```json
{
  "message": "Super admin não pode criar agendamentos"
}
```

```json
{
  "message": "Super admin não pode atualizar agendamentos"
}
```

```json
{
  "message": "Super admin não pode excluir agendamentos"
}
```

### Erro 404 - Not Found
```json
{
  "message": "Agendamento não encontrado"
}
```

### Erro 422 - Unprocessable Entity

**Validação:**
```json
{
  "message": "Erro na validação",
  "errors": {
    "service_id": ["O campo service_id é obrigatório."],
    "provider_id": ["O campo provider_id é obrigatório."],
    "client_id": ["O campo client_id é obrigatório."],
    "date_start": ["O campo date_start deve ser uma data válida."]
  }
}
```

**Fora do horário do tenant:**
```json
{
  "message": "Fora do horário de funcionamento do estabelecimento"
}
```

**Fora do horário do profissional:**
```json
{
  "message": "Fora do horário de disponibilidade do profissional"
}
```

**Horário bloqueado:**
```json
{
  "message": "Horário bloqueado"
}
```

**Conflito de horário:**
```json
{
  "message": "Conflito de horário detectado"
}
```

---

## ⚠️ Observações Importantes

1. **Formato de Data:** Use `"YYYY-MM-DD HH:mm:ss"` ou ISO 8601 para `date_start`. As datas retornadas não incluem o "Z" (UTC): `"2025-12-23T19:00:00"`
2. **Cálculo Automático:** O campo `date_end` é calculado automaticamente: `date_start + duration_minutes` (do serviço)
3. **Hierarquia de Validação (REGRA MESTRE):** A ordem de validação NÃO pode ser invertida:
   - **1º:** Horário do Tenant (nada pode acontecer fora)
   - **2º:** Horário do Profissional (sempre dentro do tenant)
   - **3º:** Bloqueios (folgas, almoços, etc.)
   - **4º:** Conflitos com outros agendamentos
4. **Client ID:** Deve ser um `user_id` válido (pode ser aluno ou cliente futuro)
5. **Status Agenda:** É opcional; se não informado, fica `null`
6. **Super Admin:** Não pode criar, atualizar ou excluir agendamentos (apenas visualizar)
7. **Filtro Automático:** Todos os dados são filtrados automaticamente por `tenant_id` do usuário logado
8. **Validações de Horário:**
   - O sistema verifica se o horário está dentro do horário de funcionamento do tenant
   - O sistema verifica se o horário está dentro da disponibilidade do profissional
   - O sistema verifica se não há bloqueio no horário
   - O sistema verifica sobreposição de horários considerando:
     - `date_start` do novo agendamento está entre `date_start` e `date_end` de outro
     - `date_end` do novo agendamento está entre `date_start` e `date_end` de outro
     - O novo agendamento engloba completamente outro agendamento
9. **Recálculo Automático:** Ao atualizar `service_id` ou `date_start`, o `date_end` é recalculado automaticamente
10. **Horário do Profissional:** O profissional não pode ultrapassar o horário do tenant. Se o tenant funciona 09:00–19:00 e o profissional está disponível 08:00–20:00, apenas 09:00–19:00 será considerado.

---

## 🔐 Permissões Necessárias

As seguintes permissões são necessárias para usar as rotas de agendamentos:

| Permissão | Descrição | Rotas |
|-----------|-----------|-------|
| `agenda.appointments.view` | Visualizar agendamentos | GET `/api/agenda/appointments`, GET `/api/agenda/appointments/{id}` |
| `agenda.appointments.create` | Criar agendamentos | POST `/api/agenda/appointments` |
| `agenda.appointments.edit` | Editar agendamentos | PUT/PATCH `/api/agenda/appointments/{id}` |
| `agenda.appointments.delete` | Excluir agendamentos | DELETE `/api/agenda/appointments/{id}`, DELETE `/api/agenda/appointments/batch` |

**Nota:** As permissões devem ser criadas no `PermissionSeeder` se ainda não existirem:
- `agenda.appointments.view`
- `agenda.appointments.create`
- `agenda.appointments.edit`
- `agenda.appointments.delete`

---

## 📋 Resumo das Rotas

| Método | Rota | Permissão | Descrição |
|--------|------|-----------|-----------|
| GET | `/api/agenda/appointments` | `agenda.appointments.view` | Listar agendamentos |
| GET | `/api/agenda/appointments/{id}` | `agenda.appointments.view` | Buscar agendamento por ID |
| POST | `/api/agenda/appointments` | `agenda.appointments.create` | Criar agendamento |
| PUT | `/api/agenda/appointments/{id}` | `agenda.appointments.edit` | Atualizar agendamento |
| PATCH | `/api/agenda/appointments/{id}` | `agenda.appointments.edit` | Atualizar agendamento |
| DELETE | `/api/agenda/appointments/{id}` | `agenda.appointments.delete` | Excluir agendamento |
| DELETE | `/api/agenda/appointments/batch` | `agenda.appointments.delete` | Excluir múltiplos |
| DELETE | `/api/agenda/appointments` | `agenda.appointments.delete` | Excluir múltiplos |

---

---

## 📅 Buscar Agenda Completa

### Rota
```
GET /api/agenda
```

### Permissão
`agenda.appointments.view`

### Query Parameters (obrigatórios)
- `provider_id` - ID do profissional (integer)
- `start` - Data de início do período (opcional, formato: YYYY-MM-DD)
- `end` - Data de fim do período (opcional, formato: YYYY-MM-DD)

### Exemplo de Requisição
```bash
curl -X GET "http://localhost:8080/api/agenda?provider_id=2&start=2025-01-20&end=2025-01-27" \
  -H "Authorization: Bearer SEU_TOKEN" \
  -H "Content-Type: application/json"
```

### Resposta de Sucesso (200)
```json
{
  "tenant_business_hours": [
    {
      "id": 1,
      "tenant_id": 1,
      "weekday": 1,
      "start_time": "09:00:00",
      "end_time": "19:00:00",
      "active": true
    }
  ],
  "availabilities": [
    {
      "id": 1,
      "provider_id": 2,
      "weekday": 1,
      "start_time": "10:00:00",
      "end_time": "18:00:00",
      "active": true
    }
  ],
  "blocks": [
    {
      "id": 1,
      "provider_id": 2,
      "tenant_id": 1,
      "start_at": "2025-01-20T12:00:00",
      "end_at": "2025-01-20T13:00:00",
      "reason": "Almoço",
      "created_by": 1
    }
  ],
  "schedules": [
    {
      "id": 1,
      "date_start": "2025-01-20T14:30:00",
      "date_end": "2025-01-20T15:00:00",
      "service": {...},
      "provider": {...},
      "client": {...}
    }
  ]
}
```

**Observação:** Retorna todos os dados necessários para montar a agenda completa: horários do tenant, disponibilidades do profissional, bloqueios e agendamentos.

---

**Documentação atualizada em:** 2025-12-21

