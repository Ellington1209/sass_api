# Seeder

## Visão Geral

O seeder cria o usuário super administrador inicial do sistema.

## Arquivo

**Localização**: `database/seeders/DatabaseSeeder.php`

## Usuário Criado

### Super Admin

**Dados**:
- **Nome**: Ellington Machado de Paula
- **Email**: ellington@admin.com
- **Senha**: Tonemara89 (será hasheada automaticamente)
- **is_super_admin**: `true`
- **tenant_id**: `null`

### Características

- ✅ Super administrador (`is_super_admin = true`)
- ✅ Sem tenant (`tenant_id = null`)
- ✅ Acesso total ao sistema
- ✅ Ignora verificações de permissão e tenant

## Código

```php
<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'nome' => 'Ellington Machado de Paula',
            'email' => 'ellington@admin.com',
            'password' => Hash::make('123456'),
            'is_super_admin' => true,
            'tenant_id' => null,
        ]);

        $this->command->info('Super admin user created successfully!');
        $this->command->info('Email: ellington@admin.com');
        $this->command->info('Password: Tonemara89');
    }
}
```

## Execução

### Via Artisan

```bash
# Executar o seeder
php artisan db:seed

# Ou especificando a classe
php artisan db:seed --class=DatabaseSeeder
```

### Via Docker

```bash
# Executar dentro do container
docker-compose exec app php artisan db:seed
```

### Com Migrations

```bash
# Executar migrations e seeders juntos
php artisan migrate --seed
```

## Verificação

Após executar o seeder, você pode verificar se o usuário foi criado:

### Via Tinker

```bash
php artisan tinker
```

```php
User::where('email', 'ellington@admin.com')->first();
```

### Via Login

Faça login na API:

```bash
curl -X POST http://localhost:8080/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "ellington@admin.com",
    "password": "Tonemara89"
  }'
```

## Segurança

⚠️ **Importante**: 

1. **Altere a senha após o primeiro login** em produção
2. **Não commite o arquivo `.env`** com credenciais reais
3. **Use senhas fortes** em produção
4. **Considere usar variáveis de ambiente** para dados sensíveis

## Adicionar Mais Seeders

Para adicionar mais dados iniciais, você pode:

### Opção 1: Adicionar no DatabaseSeeder

```php
public function run(): void
{
    // Super admin
    User::create([...]);

    // Criar tenant exemplo
    $tenant = Tenant::create([
        'nome' => 'Empresa Exemplo',
        'active_modules' => ['financeiro', 'rh']
    ]);

    // Criar usuário do tenant
    User::create([
        'nome' => 'João Silva',
        'email' => 'joao@exemplo.com',
        'password' => Hash::make('senha123'),
        'tenant_id' => $tenant->id,
        'is_super_admin' => false,
    ]);
}
```

### Opção 2: Criar Seeders Separados

```bash
php artisan make:seeder TenantSeeder
php artisan make:seeder PermissionSeeder
```

E chamar no `DatabaseSeeder`:

```php
public function run(): void
{
    $this->call([
        TenantSeeder::class,
        PermissionSeeder::class,
        // ...
    ]);
}
```

## Reset do Banco

Para resetar o banco e executar seeders novamente:

```bash
# Resetar e executar migrations + seeders
php artisan migrate:fresh --seed
```

⚠️ **Atenção**: `migrate:fresh` **apaga todos os dados** do banco!

