# Models

## Visão Geral

Os models utilizam Eloquent ORM do Laravel para interagir com o banco de dados.

## User Model

**Localização**: `app/Models/User.php`

### Atributos Fillable
- `tenant_id` (nullable)
- `nome`
- `email`
- `password`
- `is_super_admin` (boolean, default: false)

### Casts
- `email_verified_at`: datetime
- `password`: hashed (hash automático)
- `is_super_admin`: boolean

### Relacionamentos

#### `tenant()`
```php
public function tenant()
{
    return $this->belongsTo(Tenant::class);
}
```
- Relacionamento: User pertence a um Tenant (opcional)

#### `userPermissions()`
```php
public function userPermissions()
{
    return $this->hasMany(UserPermission::class);
}
```
- Relacionamento: User tem muitas UserPermission

### Accessors

#### `getPermissionsAttribute()`
Retorna array de `permission_key` do usuário.

**Lógica**:
- Se `is_super_admin = true`: retorna array vazio `[]`
- Caso contrário: retorna array com todas as `permission_key` de `user_permissions`

**Uso**: `$user->permissions`

#### `getModulesAttribute()`
Retorna array de módulos ativos do tenant do usuário.

**Lógica**:
- Se `is_super_admin = true`: retorna array vazio `[]`
- Se `tenant_id` existe: retorna `tenant->active_modules` (JSON decodificado)
- Se `tenant_id = null`: retorna array vazio `[]`

**Uso**: `$user->modules`

### Traits
- `HasApiTokens`: Para autenticação com Sanctum
- `HasFactory`: Para factories de teste
- `Notifiable`: Para notificações

---

## Tenant Model

**Localização**: `app/Models/Tenant.php`

### Atributos Fillable
- `nome`
- `active_modules` (JSON)

### Casts
- `active_modules`: array (JSON automático)

### Relacionamentos

#### `users()`
```php
public function users()
{
    return $this->hasMany(User::class);
}
```
- Relacionamento: Tenant tem muitos Users

### Exemplo de Uso
```php
$tenant = Tenant::create([
    'nome' => 'Empresa ABC',
    'active_modules' => ['financeiro', 'autoescola', 'rh']
]);

// active_modules será automaticamente convertido de/para JSON
$modules = $tenant->active_modules; // ['financeiro', 'autoescola', 'rh']
```

---

## Permission Model

**Localização**: `app/Models/Permission.php`

### Atributos Fillable
- `permission_key` (unique)
- `module`
- `descricao` (nullable)

### Estrutura
Tabela de catálogo de permissões disponíveis no sistema.

**Exemplo**:
```php
Permission::create([
    'permission_key' => 'users.ver',
    'module' => 'users',
    'descricao' => 'Permite visualizar usuários'
]);
```

---

## UserPermission Model

**Localização**: `app/Models/UserPermission.php`

### Atributos Fillable
- `user_id`
- `permission_key`

### Relacionamentos

#### `user()`
```php
public function user()
{
    return $this->belongsTo(User::class);
}
```
- Relacionamento: UserPermission pertence a um User

### Estrutura
Tabela pivot que relaciona usuários com permissões.

**Unique Constraint**: `['user_id', 'permission_key']` (evita duplicatas)

**Exemplo**:
```php
UserPermission::create([
    'user_id' => 1,
    'permission_key' => 'users.ver'
]);
```

---

## Convenções

- Todos os models usam `HasFactory` para testes
- Nomes de tabelas seguem convenção plural do Laravel
- Relacionamentos seguem convenções do Eloquent
- Accessors são usados para lógica de negócio relacionada a dados do model

