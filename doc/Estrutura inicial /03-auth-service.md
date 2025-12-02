# Auth Service

## Localização

`modules/Auth/Services/AuthService.php`

## Responsabilidades

O `AuthService` contém toda a lógica de negócio relacionada à autenticação, separando as regras de negócio do controller.

## Métodos

### 1. `login(string $email, string $password): ?array`

**Descrição**: Processa o login do usuário, verificando credenciais e gerando token.

**Parâmetros**:
- `$email`: Email do usuário
- `$password`: Senha em texto plano

**Retorno**: 
- `array` com dados do usuário, permissões, módulos e token em caso de sucesso
- `null` em caso de credenciais inválidas

**Lógica**:
1. Busca o usuário pelo email, carregando relacionamentos (`userPermissions`, `tenant`)
2. Verifica se o usuário existe e se a senha está correta usando `Hash::check()`
3. Cria um token Sanctum com o nome `'auth-token'`
4. Retorna array formatado com:
   - Dados do usuário (id, nome, email, tenant_id, is_super_admin)
   - Permissões (através do accessor `$user->permissions`)
   - Módulos (através do accessor `$user->modules`)
   - Token Sanctum

**Exemplo de Retorno**:
```php
[
    'user' => [
        'id' => 1,
        'nome' => 'Ellington Machado de Paula',
        'email' => 'ellington@admin.com',
        'tenant_id' => null,
        'is_super_admin' => true,
    ],
    'permissions' => [],
    'modules' => [],
    'token' => '1|abc123def456...',
]
```

---

### 2. `getMe(User $user): array`

**Descrição**: Formata os dados do usuário autenticado para retorno na rota `/me`.

**Parâmetros**:
- `$user`: Instância do modelo `User` autenticado

**Retorno**: `array` com dados do usuário, permissões e módulos (sem token)

**Lógica**:
1. Carrega os relacionamentos necessários (`userPermissions`, `tenant`)
2. Retorna array formatado com:
   - Dados do usuário
   - Permissões (através do accessor `$user->permissions`)
   - Módulos (através do accessor `$user->modules`)

**Exemplo de Retorno**:
```php
[
    'user' => [
        'id' => 1,
        'nome' => 'Ellington Machado de Paula',
        'email' => 'ellington@admin.com',
        'tenant_id' => null,
        'is_super_admin' => true,
    ],
    'permissions' => [],
    'modules' => [],
]
```

## Regras de Negócio

### Super Admin
- Super admins (`is_super_admin = true`) sempre retornam arrays vazios para `permissions` e `modules`
- Super admins não precisam de tenant
- Super admins têm acesso total ao sistema

### Funcionários do Super Admin
- Usuários com `tenant_id = null` e `is_super_admin = false` são funcionários do super admin
- Seguem permissões normais
- Não têm módulos associados (pois não têm tenant)

### Usuários Normais
- Usuários com `tenant_id` definido pertencem a um tenant específico
- Permissões vêm de `user_permissions`
- Módulos vêm de `tenant.active_modules`

## Dependências

- **App\Models\User**: Model de usuário
- **Illuminate\Support\Facades\Hash**: Para verificação de senha
- **Laravel Sanctum**: Para criação de tokens

