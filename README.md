# Laravel SaaS - Módulo de Autenticação

Projeto Laravel 12+ com Docker, Postgres e módulo de autenticação.

## Estrutura

- **Docker**: docker-compose.yml com app, nginx, postgres e pgadmin
- **Módulo Auth**: `/modules/Auth` com Controllers, Services e routes
- **Migrations**: users, tenants, permissions, user_permissions
- **Middlewares**: CheckTenant e CheckPermission

## Setup

1. Copie o arquivo `.env.example` para `.env`:
```bash
cp .env.example .env
```

2. Suba os containers:
```bash
docker-compose up -d
```

3. Entre no container do app:
```bash
docker-compose exec app bash
```

4. Instale as dependências (se necessário):
```bash
composer install
```

5. Gere a chave da aplicação:
```bash
php artisan key:generate
```

6. Execute as migrations:
```bash
php artisan migrate
```

7. Execute o seeder para criar o super admin:
```bash
php artisan db:seed
```

## Super Admin

- **Email**: ellington@admin.com
- **Senha**: Tonemara89

## Rotas de Autenticação

- `POST /api/auth/login` - Login
- `POST /api/auth/logout` - Logout (requer autenticação)
- `GET /api/auth/me` - Dados do usuário logado (requer autenticação)

## Resposta do Login

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
  "token": "TOKEN_SANCTUM"
}
```

## Middlewares

- `check.tenant` - Verifica tenant (super-admin ignora)
- `check.permission` - Verifica permissão (super-admin ignora)

## Portas

- **API**: http://localhost:8080
- **PgAdmin**: http://localhost:5050

