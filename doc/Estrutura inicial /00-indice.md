# Documentação do Projeto Laravel SaaS

## Índice

Esta documentação descreve todos os componentes do projeto Laravel SaaS com módulo de autenticação.

### 📁 Estrutura

1. **[Estrutura do Projeto](./01-estrutura-projeto.md)**
   - Visão geral da arquitetura
   - Estrutura de diretórios
   - Convenções e padrões

### 🔐 Autenticação

2. **[Auth Controller](./02-auth-controller.md)**
   - Endpoints de autenticação
   - Validações e respostas
   - Métodos: login, logout, me

3. **[Auth Service](./03-auth-service.md)**
   - Lógica de negócio de autenticação
   - Regras de super admin, funcionários e usuários
   - Geração de tokens

### 🗄️ Banco de Dados

4. **[Models](./04-models.md)**
   - User, Tenant, Permission, UserPermission
   - Relacionamentos Eloquent
   - Accessors e lógica de negócio

5. **[Migrations](./05-migrations.md)**
   - Estrutura das tabelas
   - Relacionamentos e constraints
   - Ordem de execução

### 🛡️ Segurança

6. **[Middlewares](./06-middlewares.md)**
   - CheckTenant: Verificação de tenant
   - CheckPermission: Verificação de permissões
   - Hierarquia de acesso

### 🛣️ Rotas

7. **[Rotas](./07-rotas.md)**
   - Rotas do módulo Auth
   - Carregamento automático de módulos
   - Autenticação com Sanctum

### 🐳 Docker

8. **[Docker](./08-docker.md)**
   - Configuração dos containers
   - Serviços: app, nginx, postgres, pgadmin
   - Comandos úteis

### 🌱 Dados Iniciais

9. **[Seeder](./09-seeder.md)**
   - Criação do super admin
   - Execução e verificação

---

## Quick Start

1. **Subir containers**: `docker-compose up -d`
2. **Instalar dependências**: `docker-compose exec app composer install`
3. **Configurar .env**: Copiar `.env.example` para `.env`
4. **Gerar chave**: `docker-compose exec app php artisan key:generate`
5. **Executar migrations**: `docker-compose exec app php artisan migrate`
6. **Executar seeder**: `docker-compose exec app php artisan db:seed`
7. **Acessar**: http://localhost:8080

## Credenciais Padrão

- **Email**: ellington@admin.com
- **Senha**: Tonemara89

## Endpoints Principais

- `POST /api/auth/login` - Login
- `POST /api/auth/logout` - Logout (requer autenticação)
- `GET /api/auth/me` - Dados do usuário (requer autenticação)

---

**Última atualização**: Janeiro 2024

