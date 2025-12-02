# Estrutura do Projeto

## Visão Geral

Projeto Laravel 12+ modular com Docker, PostgreSQL e sistema de autenticação multi-tenant.

## Estrutura de Diretórios

```
Sass/
├── app/
│   ├── Http/
│   │   ├── Controllers/          # Controllers principais (vazio por enquanto)
            ├── modules/                      # Módulos da aplicação
            │   └── Auth/                     # Módulo de Autenticação
            │       ├── Controllers/
            │       │   └── AuthController.php
            │       ├── Services/
            │       │   └── AuthService.php
│       └── routes.php            # Rotas do módulo
│   │   └── Middleware/           # Middlewares customizados
│   │       ├── CheckTenant.php   # Middleware de verificação de tenant
│   │       └── CheckPermission.php # Middleware de verificação de permissão
│   ├── Models/                   # Models Eloquent
│   │   ├── User.php              # Model de usuário
│   │   ├── Tenant.php              # Model de tenant
│   │   ├── Permission.php        # Model de permissão
│   │   └── UserPermission.php   # Model de relação usuário-permissão
│   └── Providers/
│       └── RouteServiceProvider.php # Provider de rotas
├── bootstrap/
│   ├── app.php                   # Bootstrap principal (loader de módulos)
│   ├── cache/                    # Cache do bootstrap
│   └── providers.php             # Lista de providers
├── config/
│   ├── app.php                   # Configurações da aplicação
│   ├── auth.php                  # Configurações de autenticação
│   ├── database.php              # Configurações do banco de dados
│   └── sanctum.php               # Configurações do Sanctum
├── database/
│   ├── migrations/               # Migrations do banco
│   │   ├── 2024_01_01_000001_create_tenants_table.php
│   │   ├── 2024_01_01_000002_create_users_table.php
│   │   ├── 2024_01_01_000003_create_permissions_table.php
│   │   └── 2024_01_01_000004_create_user_permissions_table.php
│   └── seeders/
│       └── DatabaseSeeder.php    # Seeder principal (super admin)
├── docker/
│   └── nginx/
│       └── default.conf          # Configuração do Nginx

├── public/
│   └── index.php                 # Entry point da aplicação
├── routes/
│   ├── api.php                   # Rotas da API
│   ├── console.php               # Comandos do console
│   └── web.php                   # Rotas web
├── storage/                      # Arquivos de storage
│   ├── framework/
│   │   ├── cache/
│   │   ├── sessions/
│   │   └── views/
│   └── logs/
├── docker-compose.yml            # Configuração Docker Compose
├── Dockerfile                    # Imagem Docker da aplicação
└── composer.json                 # Dependências PHP
```

## Arquitetura Modular

O projeto utiliza uma arquitetura modular onde cada funcionalidade é organizada em módulos dentro da pasta `modules/`.

### Carregamento Automático de Módulos

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

Qualquer módulo que tenha um arquivo `routes.php` na raiz será automaticamente carregado com o prefixo `/api`.

## Stack Tecnológica

- **Framework**: Laravel 12+
- **PHP**: 8.3
- **Banco de Dados**: PostgreSQL 16
- **Autenticação**: Laravel Sanctum
- **Containerização**: Docker + Docker Compose
- **Web Server**: Nginx

## Convenções

- **Nomes de tabelas**: Inglês, plural (users, tenants, permissions)
- **Nomes de colunas**: Inglês (nome, email, tenant_id, is_super_admin)
- **Namespaces**: `App\` para código principal, `Modules\` para módulos
- **Estrutura de módulos**: `modules/{ModuleName}/{Controllers|Services}/`

