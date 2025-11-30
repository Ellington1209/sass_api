# Rotas

## Visão Geral

O sistema utiliza rotas modulares, onde cada módulo define suas próprias rotas em um arquivo `routes.php`.

## Estrutura de Rotas

### Rotas Principais

**Arquivo**: `routes/api.php`
- `GET /api/health` - Health check da API

**Arquivo**: `routes/web.php`
- `GET /` - Mensagem de boas-vindas

### Rotas do Módulo Auth

**Arquivo**: `modules/Auth/routes.php`

Todas as rotas do módulo Auth são prefixadas com `/api/auth`.

#### 1. Login

**Rota**: `POST /api/auth/login`

**Middleware**: Nenhum (pública)

**Controller**: `Modules\Auth\Controllers\AuthController@login`

**Body**:
```json
{
  "email": "ellington@admin.com",
  "password": "Tonemara89"
}
```

**Resposta de Sucesso**:
```json
{
  "user": {
    "id": 1,
    "nome": "Ellington Machado de Paula",
    "email": "ellington@admin.com",
    "tenant_id": null,
    "is_super_admin": true
  },
  "permissions": [],
  "modules": [],
  "token": "1|abc123def456..."
}
```

**Resposta de Erro**:
```json
{
  "message": "Invalid credentials"
}
```
Status: `401`

---

#### 2. Logout

**Rota**: `POST /api/auth/logout`

**Middleware**: `auth:sanctum`

**Controller**: `Modules\Auth\Controllers\AuthController@logout`

**Headers**:
```
Authorization: Bearer {token}
```

**Resposta de Sucesso**:
```json
{
  "message": "Logged out successfully"
}
```

---

#### 3. Me (Dados do Usuário)

**Rota**: `GET /api/auth/me`

**Middleware**: `auth:sanctum`

**Controller**: `Modules\Auth\Controllers\AuthController@me`

**Headers**:
```
Authorization: Bearer {token}
```

**Resposta de Sucesso**:
```json
{
  "user": {
    "id": 1,
    "nome": "Ellington Machado de Paula",
    "email": "ellington@admin.com",
    "tenant_id": null,
    "is_super_admin": true
  },
  "permissions": [],
  "modules": []
}
```

---

## Carregamento Automático de Módulos

O sistema carrega automaticamente as rotas de todos os módulos através do `bootstrap/app.php`:

```php
->withRouting(
    // ... outras rotas
    then: function () {
        // Loader automático de módulos
        foreach (glob(base_path('modules/*/routes.php')) as $file) {
            Route::middleware('api')
                ->prefix('api')
                ->group($file);
        }
    },
)
```

### Como Funciona

1. O sistema busca todos os arquivos `routes.php` dentro de `modules/*/`
2. Cada arquivo é carregado com:
   - Middleware `api`
   - Prefixo `/api`
3. As rotas definidas dentro do arquivo herdam essas configurações

### Exemplo de Estrutura

```
modules/
├── Auth/
│   └── routes.php          # Carregado automaticamente
├── Financeiro/
│   └── routes.php          # Será carregado automaticamente quando criado
└── Users/
    └── routes.php          # Será carregado automaticamente quando criado
```

### Definindo Rotas em um Módulo

```php
<?php
// modules/Auth/routes.php

use Illuminate\Support\Facades\Route;
use Modules\Auth\Controllers\AuthController;

Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
    });
});
```

**Resultado**: As rotas serão acessíveis em:
- `/api/auth/login`
- `/api/auth/logout`
- `/api/auth/me`

---

## Autenticação com Sanctum

### Como Usar o Token

Após fazer login, você receberá um token. Use esse token no header `Authorization`:

```
Authorization: Bearer 1|abc123def456...
```

### Exemplo com cURL

```bash
# Login
curl -X POST http://localhost:8080/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "ellington@admin.com",
    "password": "Tonemara89"
  }'

# Me (com token)
curl -X GET http://localhost:8080/api/auth/me \
  -H "Authorization: Bearer 1|abc123def456..."
```

### Exemplo com JavaScript (Fetch)

```javascript
// Login
const loginResponse = await fetch('http://localhost:8080/api/auth/login', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
  },
  body: JSON.stringify({
    email: 'ellington@admin.com',
    password: 'Tonemara89'
  })
});

const { token } = await loginResponse.json();

// Me
const meResponse = await fetch('http://localhost:8080/api/auth/me', {
  headers: {
    'Authorization': `Bearer ${token}`
  }
});

const userData = await meResponse.json();
```

---

## Convenções

1. **Rotas públicas**: Sem middleware de autenticação
2. **Rotas protegidas**: Sempre usar `auth:sanctum`
3. **Prefixo de módulos**: Cada módulo define seu próprio prefixo dentro do grupo
4. **Nomes de rotas**: Usar kebab-case (ex: `/auth/login`, `/users/list`)
5. **Verbos HTTP**: 
   - `GET` para leitura
   - `POST` para criação/ações
   - `PUT/PATCH` para atualização
   - `DELETE` para exclusão

## Próximos Módulos

Quando novos módulos forem criados, basta criar um arquivo `routes.php` dentro do módulo e ele será carregado automaticamente:

```
modules/
└── Financeiro/
    └── routes.php  # Será carregado como /api/financeiro/*
```

