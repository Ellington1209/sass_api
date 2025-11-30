# Middlewares

## Visão Geral

Os middlewares são responsáveis por interceptar requisições HTTP e aplicar lógica antes que cheguem aos controllers.

## Middlewares Criados

### 1. CheckTenant

**Localização**: `app/Http/Middleware/CheckTenant.php`

**Registro**: Registrado no `bootstrap/app.php` como `check.tenant`

**Uso**:
```php
Route::middleware(['auth:sanctum', 'check.tenant'])->group(function () {
    // Rotas protegidas
});
```

#### Funcionalidade

Verifica e aplica regras relacionadas ao tenant do usuário autenticado.

#### Lógica

1. **Verifica Autenticação**:
   - Se não houver usuário autenticado, retorna 401 (Unauthenticated)

2. **Super Admin**:
   - Se `is_super_admin = true`: **ignora** verificação de tenant
   - Permite acesso direto

3. **Funcionário do Super Admin**:
   - Se `tenant_id = null` e `is_super_admin = false`: permite acesso
   - São funcionários do super admin que não têm tenant específico

4. **Usuário Normal**:
   - Se `tenant_id` existe: aplica regras do tenant
   - Pode adicionar lógica adicional aqui (ex: verificar se tenant está ativo)

#### Resposta de Erro

```json
{
  "message": "Unauthenticated"
}
```
Status: `401`

#### Exemplo de Uso

```php
// Em uma rota
Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth:sanctum', 'check.tenant']);
```

---

### 2. CheckPermission

**Localização**: `app/Http/Middleware/CheckPermission.php`

**Registro**: Registrado no `bootstrap/app.php` como `check.permission`

**Uso**:
```php
Route::middleware(['auth:sanctum', 'check.permission:users.ver'])->get('/users', ...);
```

#### Funcionalidade

Verifica se o usuário autenticado possui uma permissão específica.

#### Parâmetros

- `$permission`: Nome da permissão requerida (ex: `users.ver`, `financeiro.editar`)

#### Lógica

1. **Verifica Autenticação**:
   - Se não houver usuário autenticado, retorna 401 (Unauthenticated)

2. **Super Admin**:
   - Se `is_super_admin = true`: **ignora** verificação de permissão
   - Permite acesso direto (super admin tem todas as permissões)

3. **Verificação de Permissão**:
   - Obtém array de permissões do usuário através do accessor `$user->permissions`
   - Verifica se a permissão requerida está no array
   - Se não estiver, retorna 403 (Forbidden)

#### Resposta de Erro - Não Autenticado

```json
{
  "message": "Unauthenticated"
}
```
Status: `401`

#### Resposta de Erro - Sem Permissão

```json
{
  "message": "You do not have permission to access this resource"
}
```
Status: `403`

#### Exemplo de Uso

```php
// Rota que requer permissão específica
Route::get('/users', [UserController::class, 'index'])
    ->middleware(['auth:sanctum', 'check.permission:users.ver']);

Route::post('/users', [UserController::class, 'store'])
    ->middleware(['auth:sanctum', 'check.permission:users.criar']);

Route::delete('/users/{id}', [UserController::class, 'destroy'])
    ->middleware(['auth:sanctum', 'check.permission:users.deletar']);
```

#### Múltiplas Permissões

Para verificar múltiplas permissões, você pode criar um middleware adicional ou usar múltiplas rotas. Exemplo de middleware customizado:

```php
// Exemplo (não implementado ainda)
Route::middleware(['auth:sanctum', 'check.permission:users.ver,users.editar'])
```

---

## Registro de Middlewares

Os middlewares são registrados no arquivo `bootstrap/app.php`:

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'check.tenant' => \App\Http\Middleware\CheckTenant::class,
        'check.permission' => \App\Http\Middleware\CheckPermission::class,
    ]);
})
```

## Hierarquia de Acesso

### Super Admin
- ✅ Ignora verificação de tenant
- ✅ Ignora verificação de permissão
- ✅ Acesso total ao sistema

### Funcionário do Super Admin
- ✅ Pode não ter tenant (`tenant_id = null`)
- ⚠️ Deve ter permissões específicas
- ⚠️ Não tem módulos (pois não tem tenant)

### Usuário Normal
- ⚠️ Deve ter tenant (`tenant_id` definido)
- ⚠️ Deve ter permissões específicas
- ✅ Módulos vêm do tenant

## Boas Práticas

1. **Sempre use `auth:sanctum` antes dos middlewares customizados**
2. **Super admin sempre tem acesso** - não precisa verificar permissões
3. **Use nomes descritivos para permissões** (ex: `module.acao`)
4. **Documente quais permissões cada rota requer**

