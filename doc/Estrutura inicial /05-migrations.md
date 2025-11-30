# Migrations

## Visão Geral

As migrations definem a estrutura do banco de dados PostgreSQL.

## Tabelas Criadas

### 1. `tenants`

**Arquivo**: `2024_01_01_000001_create_tenants_table.php`

**Estrutura**:
```sql
CREATE TABLE tenants (
    id BIGSERIAL PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    active_modules JSON,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

**Campos**:
- `id`: Chave primária auto-incremento
- `nome`: Nome do tenant (string)
- `active_modules`: Módulos ativos do tenant (JSON, nullable)
- `timestamps`: created_at e updated_at

**Uso**: Armazena informações dos tenants (empresas/clientes) do sistema multi-tenant.

---

### 2. `users`

**Arquivo**: `2024_01_01_000002_create_users_table.php`

**Estrutura**:
```sql
CREATE TABLE users (
    id BIGSERIAL PRIMARY KEY,
    tenant_id BIGINT NULL,
    nome VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    email_verified_at TIMESTAMP NULL,
    password VARCHAR(255) NOT NULL,
    is_super_admin BOOLEAN DEFAULT FALSE,
    remember_token VARCHAR(100) NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
);
```

**Campos**:
- `id`: Chave primária auto-incremento
- `tenant_id`: FK para tenants (nullable) - usuário pode não ter tenant
- `nome`: Nome do usuário
- `email`: Email único do usuário
- `email_verified_at`: Data de verificação do email (nullable)
- `password`: Senha hasheada
- `is_super_admin`: Flag booleana para super administrador
- `remember_token`: Token para "lembrar-me"
- `timestamps`: created_at e updated_at

**Constraints**:
- `email`: UNIQUE
- `tenant_id`: FOREIGN KEY com CASCADE DELETE

**Tipos de Usuários**:
1. **Super Admin**: `is_super_admin = true`, `tenant_id = null`
2. **Funcionário do Super Admin**: `is_super_admin = false`, `tenant_id = null`
3. **Usuário Normal**: `is_super_admin = false`, `tenant_id = X`

---

### 3. `permissions`

**Arquivo**: `2024_01_01_000003_create_permissions_table.php`

**Estrutura**:
```sql
CREATE TABLE permissions (
    id BIGSERIAL PRIMARY KEY,
    permission_key VARCHAR(255) UNIQUE NOT NULL,
    module VARCHAR(255) NOT NULL,
    descricao TEXT NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

**Campos**:
- `id`: Chave primária auto-incremento
- `permission_key`: Chave única da permissão (ex: "users.ver", "financeiro.editar")
- `module`: Módulo ao qual a permissão pertence
- `descricao`: Descrição opcional da permissão
- `timestamps`: created_at e updated_at

**Constraints**:
- `permission_key`: UNIQUE

**Uso**: Catálogo de todas as permissões disponíveis no sistema.

**Exemplos de permission_key**:
- `users.ver`
- `users.criar`
- `users.editar`
- `users.deletar`
- `financeiro.ver`
- `financeiro.editar`

---

### 4. `user_permissions`

**Arquivo**: `2024_01_01_000004_create_user_permissions_table.php`

**Estrutura**:
```sql
CREATE TABLE user_permissions (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL,
    permission_key VARCHAR(255) NOT NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE (user_id, permission_key)
);
```

**Campos**:
- `id`: Chave primária auto-incremento
- `user_id`: FK para users
- `permission_key`: Chave da permissão (string, não é FK)
- `timestamps`: created_at e updated_at

**Constraints**:
- `user_id`: FOREIGN KEY com CASCADE DELETE
- `(user_id, permission_key)`: UNIQUE (evita duplicatas)

**Uso**: Tabela pivot que relaciona usuários com suas permissões específicas.

**Nota**: `permission_key` é uma string, não uma FK. Isso permite flexibilidade para adicionar permissões sem precisar criar registros na tabela `permissions` primeiro.

---

## Ordem de Execução

As migrations são executadas na seguinte ordem:

1. `tenants` - Deve ser criada primeiro (users depende dela)
2. `users` - Depende de tenants
3. `permissions` - Independente
4. `user_permissions` - Depende de users

## Convenções

- Todas as tabelas têm `id` como chave primária
- Todas as tabelas têm `timestamps` (created_at, updated_at)
- Nomes em inglês
- Foreign keys com `ON DELETE CASCADE` para manter integridade
- Uso de JSON para campos complexos (`active_modules`)

## Execução

```bash
# Executar todas as migrations
php artisan migrate

# Reverter última migration
php artisan migrate:rollback

# Reverter todas as migrations
php artisan migrate:reset
```

