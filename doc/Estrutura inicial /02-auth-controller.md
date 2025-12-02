# Auth Controller

## Localização

`modules/Auth/Controllers/AuthController.php`

## Responsabilidades

O `AuthController` é responsável por gerenciar as requisições HTTP relacionadas à autenticação de usuários.

## Métodos

### 1. `login(Request $request): JsonResponse`

**Rota**: `POST /api/auth/login`

**Descrição**: Autentica um usuário e retorna um token Sanctum junto com informações do usuário.

**Validação**:
- `email`: obrigatório, deve ser um email válido
- `password`: obrigatório, string

**Resposta de Sucesso (200)**:
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

**Resposta de Erro - Validação (422)**:
```json
{
  "message": "Validation error",
  "errors": {
    "email": ["The email field is required."],
    "password": ["The password field is required."]
  }
}
```

**Resposta de Erro - Credenciais Inválidas (401)**:
```json
{
  "message": "Invalid credentials"
}
```

**Lógica**:
1. Valida os dados de entrada
2. Chama `AuthService::login()` para processar a autenticação
3. Retorna token e dados do usuário em caso de sucesso
4. Retorna erro em caso de falha

---

### 2. `logout(Request $request): JsonResponse`

**Rota**: `POST /api/auth/logout`

**Middleware**: `auth:sanctum`

**Descrição**: Revoga o token de autenticação atual do usuário.

**Resposta de Sucesso (200)**:
```json
{
  "message": "Logged out successfully"
}
```

**Lógica**:
1. Obtém o usuário autenticado através do token
2. Deleta o token atual (`currentAccessToken()`)
3. Retorna mensagem de sucesso

---

### 3. `me(Request $request): JsonResponse`

**Rota**: `GET /api/auth/me`

**Middleware**: `auth:sanctum`

**Descrição**: Retorna os dados do usuário autenticado, suas permissões e módulos.

**Resposta de Sucesso (200)**:
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

**Lógica**:
1. Obtém o usuário autenticado através do token
2. Chama `AuthService::getMe()` para formatar os dados
3. Retorna informações do usuário, permissões e módulos

## Dependências

- **AuthService**: Serviço que contém a lógica de negócio de autenticação
- **Laravel Sanctum**: Para gerenciamento de tokens

## Injeção de Dependência

O controller utiliza injeção de dependência no construtor:

```php
public function __construct(
    private AuthService $authService
) {}
```

Isso garante que o `AuthService` seja automaticamente injetado pelo container do Laravel.

