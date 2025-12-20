# Providers CRUD - Documentação

## Visão Geral

O sistema de Providers (Profissionais) permite gerenciar profissionais que prestam serviços no sistema de agenda. Cada provider está vinculado a um usuário, uma pessoa e pode ter múltiplos serviços associados.

## Estrutura de Dados

### Relacionamentos

```
User (usuário)
  ↓
Person (dados pessoais + foto)
  ↓
Provider (profissional + serviços)
```

### Tabelas Envolvidas

1. **users** - Dados de autenticação (name, email, password)
2. **persons** - Dados pessoais (cpf, rg, birth_date, phone, address, **photo_url**)
3. **providers** - Dados do profissional (tenant_id, person_id, service_ids)

## Rotas da API

### Base URL
```
/api/agenda/providers
```

### Endpoints

| Método | Rota | Permissão | Descrição |
|--------|------|-----------|-----------|
| GET | `/` | `agenda.providers.view` | Lista todos os providers |
| GET | `/{id}` | `agenda.providers.view` | Busca um provider por ID |
| POST | `/` | `agenda.providers.create` | Cria um novo provider |
| PUT | `/{id}` | `agenda.providers.edit` | Atualiza um provider |
| PATCH | `/{id}` | `agenda.providers.edit` | Atualiza parcialmente um provider |
| DELETE | `/{id}` | `agenda.providers.delete` | Remove um provider |
| DELETE | `/batch` | `agenda.providers.delete` | Remove múltiplos providers |
| DELETE | `/` | `agenda.providers.delete` | Remove providers (com filtros) |

## Criação de Provider (POST)

### Payload

**Campos Obrigatórios:**
- `name` (string, max 255) - Nome do profissional
- `email` (string, email válido, único) - Email do usuário
- `cpf` (string, 14 caracteres, formato: 000.000.000-00, único por tenant)
- `birth_date` (date, formato: YYYY-MM-DD) - Data de nascimento

**Campos Opcionais:**
- `rg` (string, max 20)
- `phone` (string, max 20)
- `address_street` (string, max 255)
- `address_number` (string, max 20)
- `address_neighborhood` (string, max 255)
- `address_city` (string, max 255)
- `address_state` (string, 2 caracteres) - Estado (ex: "GO", "SP")
- `address_zip` (string, max 10) - CEP
- `service_ids` (array de inteiros) - IDs dos serviços que o provider pode realizar
- `photo` (arquivo de imagem) - Foto do profissional (JPEG, PNG, GIF, WEBP, max 5MB)
- `photo_url` (string ou arquivo) - URL da foto ou arquivo de imagem

### Exemplo de Payload (JSON)

```json
{
  "name": "João Silva",
  "email": "joao.silva@example.com",
  "cpf": "123.456.789-00",
  "rg": "1234567",
  "birth_date": "1990-05-15",
  "phone": "(62) 99999-9999",
  "address_street": "Rua das Flores",
  "address_number": "123",
  "address_neighborhood": "Centro",
  "address_city": "Goiânia",
  "address_state": "GO",
  "address_zip": "74000-000",
  "service_ids": [1, 2, 3]
}
```

### Exemplo de Payload (FormData - para incluir foto)

```
name: João Silva
email: joao.silva@example.com
cpf: 123.456.789-00
rg: 1234567
birth_date: 1990-05-15
phone: (62) 99999-9999
address_street: Rua das Flores
address_number: 123
address_neighborhood: Centro
address_city: Goiânia
address_state: GO
address_zip: 74000-000
service_ids[0]: 1
service_ids[1]: 2
service_ids[2]: 3
photo: [arquivo de imagem]
```

### Resposta de Sucesso (201)

```json
{
  "id": 1,
  "tenant_id": 2,
  "person_id": 5,
  "user": {
    "id": 10,
    "name": "João Silva",
    "email": "joao.silva@example.com"
  },
  "person": {
    "id": 5,
    "cpf": "123.456.789-00",
    "rg": "1234567",
    "birth_date": "1990-05-15",
    "phone": "(62) 99999-9999",
    "address": {
      "street": "Rua das Flores",
      "number": "123",
      "complement": null,
      "neighborhood": "Centro",
      "city": "Goiânia",
      "state": "GO",
      "zip": "74000-000"
    }
  },
  "photo_url": "http://localhost:8080/api/files/public/tenants%2F2%2Favatars%2Fuuid.jpeg",
  "service_ids": [1, 2, 3],
  "services": [
    {
      "id": 1,
      "name": "Aulas Práticas",
      "slug": "aulas-praticas"
    },
    {
      "id": 2,
      "name": "Aulas Teóricas",
      "slug": "aulas-teoricas"
    },
    {
      "id": 3,
      "name": "Simulado",
      "slug": "simulado"
    }
  ],
  "created_at": "2025-12-17T10:30:00.000000Z",
  "updated_at": "2025-12-17T10:30:00.000000Z"
}
```

## Atualização de Provider (PUT/PATCH)

### Payload

Similar ao POST, mas todos os campos são opcionais (usar `sometimes` na validação).

**Observações:**
- O `cpf` deve ser único por tenant (ignorando o próprio provider)
- O `email` deve ser único na tabela `users` (ignorando o próprio usuário)
- O `service_ids` pode ser enviado como array indexado: `service_ids[0]`, `service_ids[1]`, etc.
- A foto pode ser atualizada enviando um novo arquivo ou removida enviando `null`

### Exemplo de Payload (FormData)

```
name: João Silva Atualizado
email: joao.silva.novo@example.com
cpf: 123.456.789-00
service_ids[0]: 1
service_ids[1]: 2
service_ids[2]: 16
photo: [novo arquivo de imagem]
```

## Listagem de Providers (GET)

### Resposta

```json
[
  {
    "id": 1,
    "tenant_id": 2,
    "person_id": 1,
    "user": {
      "id": 4,
      "name": "Geralda Borges De Araujo",
      "email": "geralda@gmail.com"
    },
    "person": {
      "id": 1,
      "cpf": "546.546.546-46",
      "rg": "123456",
      "birth_date": "2025-12-16",
      "phone": "(62) 99172-0735",
      "address": {
        "street": "rua",
        "number": "19",
        "complement": null,
        "neighborhood": "Setor Universitário",
        "city": "Goianésia",
        "state": "GO",
        "zip": "74555-250"
      }
    },
    "photo_url": "http://localhost:8080/api/files/public/tenants%2F2%2Favatars%2Fuuid.jpeg",
    "service_ids": [1, 2, 16],
    "services": [
      {
        "id": 1,
        "name": "Aulas Práticas",
        "slug": "aulas-praticas"
      },
      {
        "id": 2,
        "name": "Aulas Teóricas",
        "slug": "aulas-teoricas"
      },
      {
        "id": 16,
        "name": "Outro Serviço",
        "slug": "outro-servico"
      }
    ],
    "created_at": "2025-12-17T02:06:18.000000Z",
    "updated_at": "2025-12-17T02:06:18.000000Z"
  }
]
```

## Fluxo de Criação

1. **Criação do User**
   - Gera senha automaticamente (primeiros 6 dígitos do CPF)
   - Cria registro na tabela `users`

2. **Atribuição de Permissões**
   - Permissões padrão atribuídas automaticamente:
     - `agenda.services.view`
     - `agenda.services.create`
     - `files.view`
     - `files.upload`
     - `files.download`
     - `students.view`
     - `students.create`

3. **Criação da Person**
   - Vincula ao `user_id` criado
   - Salva dados pessoais (cpf, rg, birth_date, phone, address)
   - Faz upload da foto se fornecida (salva em `photo_url`)

4. **Criação do Provider**
   - Vincula ao `person_id` criado
   - Salva `service_ids` (array JSON)

## Estrutura de Arquivos

### Controller
- **Localização:** `app/Http/Controllers/modules/Agenda/ProviderController.php`
- **Métodos principais:**
  - `index()` - Lista providers
  - `show($id)` - Busca provider por ID
  - `store(Request $request)` - Cria novo provider
  - `update(Request $request, int $id)` - Atualiza provider
  - `destroy(Request $request, int|string|null $id)` - Remove provider(s)

### Service
- **Localização:** `app/Services/Provider/ProviderService.php`
- **Métodos principais:**
  - `create(int $tenantId, array $data)` - Cria provider completo (User → Person → Provider)
  - `update(int $id, int $tenantId, array $data)` - Atualiza provider
  - `formatProvider(Provider $provider)` - Formata dados para resposta
  - `getServicesData(?array $serviceIds)` - Busca dados completos dos serviços
  - `uploadPhoto(UploadedFile $file, int $tenantId, int $userId)` - Upload de foto
  - `deletePhoto(string $photoPath)` - Remove foto

### Model
- **Localização:** `app/Models/Provider.php`
- **Relacionamentos:**
  - `belongsTo(Tenant)`
  - `belongsTo(Person)`
  - `hasMany(Appointment)`
- **Casts:**
  - `service_ids` → array (JSON)

## Observações Importantes

1. **Foto (photo_url)**
   - A foto é salva na tabela `persons`, não em `providers`
   - Upload feito no Backblaze B2 (S3-compatible)
   - Caminho: `tenants/{tenant_id}/avatars/{uuid}.ext`
   - URL formatada automaticamente na resposta

2. **Service IDs**
   - Armazenado como JSON na coluna `service_ids`
   - Pode ser enviado como array indexado: `service_ids[0]`, `service_ids[1]`, etc.
   - O middleware `HandlePutFormData` processa arrays indexados automaticamente
   - A resposta inclui tanto `service_ids` quanto `services` (dados completos)

3. **Senha**
   - Gerada automaticamente (primeiros 6 dígitos do CPF)
   - Não é retornada na resposta

4. **Validações**
   - CPF: único por tenant na tabela `persons`
   - Email: único na tabela `users`
   - Service IDs: devem existir na tabela `services`

5. **Permissões**
   - As permissões são atribuídas automaticamente ao criar o provider
   - Não podem ser alteradas através da API de providers (usar API de permissões)

## Middleware

O middleware `HandlePutFormData` processa requisições PUT/PATCH com `multipart/form-data` e:
- Processa arrays indexados (`service_ids[0]`, `service_ids[1]`, etc.)
- Agrupa arrays indexados em arrays normais
- Mantém compatibilidade com upload de arquivos

## Tratamento de Erros

### Erro de Validação (422)
```json
{
  "message": "Erro na validação",
  "errors": {
    "cpf": ["O campo cpf já está sendo utilizado."],
    "email": ["O campo email já está sendo utilizado."]
  }
}
```

### Provider Não Encontrado (404)
```json
{
  "message": "Profissional não encontrado"
}
```

### Tenant Não Identificado (400)
```json
{
  "message": "Tenant não identificado"
}
```

