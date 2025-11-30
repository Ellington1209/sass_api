# Docker

## Visão Geral

O projeto utiliza Docker Compose para orquestrar múltiplos containers necessários para o ambiente de desenvolvimento.

## Arquivos Docker

### docker-compose.yml

Define 4 serviços principais:

1. **app** - Container da aplicação Laravel (PHP-FPM)
2. **nginx** - Servidor web Nginx
3. **postgres** - Banco de dados PostgreSQL 16
4. **pgadmin** - Interface web para gerenciar PostgreSQL

### Dockerfile

Imagem customizada baseada em `php:8.3-fpm` com:
- Extensão PostgreSQL (`pdo_pgsql`)
- Composer instalado
- Dependências do projeto instaladas

### docker/nginx/default.conf

Configuração do Nginx para servir a aplicação Laravel.

---

## Serviços

### 1. app (Laravel Application)

**Container**: `laravel-app`

**Imagem**: Build local a partir do `Dockerfile`

**Portas**: Nenhuma exposta diretamente (acessível via nginx)

**Volumes**:
- `.` → `/var/www` (código da aplicação)

**Dependências**: 
- `postgres` (aguarda postgres estar pronto)

**Rede**: `app-net`

**Comandos Úteis**:
```bash
# Entrar no container
docker-compose exec app bash

# Executar comandos artisan
docker-compose exec app php artisan migrate
docker-compose exec app php artisan db:seed
docker-compose exec app composer install
```

---

### 2. nginx (Web Server)

**Container**: `laravel-nginx`

**Imagem**: `nginx:latest`

**Portas**:
- `8080:80` (localhost:8080 → container:80)

**Volumes**:
- `.` → `/var/www` (código da aplicação)
- `./docker/nginx/default.conf` → `/etc/nginx/conf.d/default.conf` (configuração)

**Dependências**: 
- `app` (aguarda app estar pronto)

**Rede**: `app-net`

**Acesso**: http://localhost:8080

**Configuração**:
- Root: `/var/www/public`
- PHP-FPM: `app:9000`
- Suporte a rotas do Laravel

---

### 3. postgres (Database)

**Container**: `pgdb`

**Imagem**: `postgres:16`

**Portas**:
- `5432:5432` (localhost:5432 → container:5432)

**Variáveis de Ambiente**:
- `POSTGRES_DB`: `saas`
- `POSTGRES_USER`: `user`
- `POSTGRES_PASSWORD`: `password`

**Volumes**:
- `pgdata` → `/var/lib/postgresql/data` (persistência dos dados)

**Rede**: `app-net`

**Acesso**:
- Host: `postgres` (dentro da rede Docker)
- Host: `localhost` (do host)
- Porta: `5432`
- Database: `saas`
- User: `user`
- Password: `password`

**Comandos Úteis**:
```bash
# Conectar via psql
docker-compose exec postgres psql -U user -d saas

# Backup
docker-compose exec postgres pg_dump -U user saas > backup.sql

# Restore
docker-compose exec -T postgres psql -U user saas < backup.sql
```

---

### 4. pgadmin (Database Management)

**Container**: `pgadmin`

**Imagem**: `dpage/pgadmin4`

**Portas**:
- `5050:80` (localhost:5050 → container:80)

**Variáveis de Ambiente**:
- `PGADMIN_DEFAULT_EMAIL`: `admin@admin.com`
- `PGADMIN_DEFAULT_PASSWORD`: `admin`

**Rede**: `app-net`

**Acesso**: http://localhost:5050

**Login**:
- Email: `admin@admin.com`
- Password: `admin`

**Configurar Servidor no PgAdmin**:
1. Acesse http://localhost:5050
2. Faça login
3. Clique com botão direito em "Servers" → "Register" → "Server"
4. Na aba "General":
   - Name: `Laravel SaaS`
5. Na aba "Connection":
   - Host name/address: `postgres`
   - Port: `5432`
   - Maintenance database: `saas`
   - Username: `user`
   - Password: `password`
6. Salve

---

## Rede

**Nome**: `app-net`

Todos os containers estão na mesma rede, permitindo comunicação entre eles usando os nomes dos containers:
- `app` pode acessar `postgres` usando o hostname `postgres`
- `nginx` pode acessar `app` usando o hostname `app`

---

## Volumes

### pgdata

Volume persistente para dados do PostgreSQL. Os dados são mantidos mesmo após parar/remover os containers.

**Localização**: Gerenciado pelo Docker

**Remover dados**:
```bash
docker-compose down -v
```

---

## Comandos Docker Compose

### Iniciar Containers

```bash
# Iniciar em background
docker-compose up -d

# Iniciar com logs
docker-compose up
```

### Parar Containers

```bash
# Parar (mantém containers)
docker-compose stop

# Parar e remover containers
docker-compose down

# Parar, remover containers e volumes
docker-compose down -v
```

### Ver Logs

```bash
# Todos os serviços
docker-compose logs

# Serviço específico
docker-compose logs app
docker-compose logs nginx
docker-compose logs postgres
```

### Rebuild

```bash
# Rebuild da imagem app
docker-compose build app

# Rebuild forçado
docker-compose build --no-cache app
```

### Executar Comandos

```bash
# Comando no container app
docker-compose exec app php artisan migrate

# Comando no container postgres
docker-compose exec postgres psql -U user -d saas
```

---

## Variáveis de Ambiente

As variáveis de ambiente do Laravel devem ser configuradas no arquivo `.env`:

```env
DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=saas
DB_USERNAME=user
DB_PASSWORD=password
```

**Importante**: `DB_HOST=postgres` (nome do container, não `localhost`)

---

## Troubleshooting

### Container não inicia

```bash
# Ver logs
docker-compose logs app

# Verificar status
docker-compose ps
```

### Banco de dados não conecta

1. Verifique se o container postgres está rodando: `docker-compose ps`
2. Verifique as variáveis de ambiente no `.env`
3. Teste a conexão: `docker-compose exec app php artisan tinker` → `DB::connection()->getPdo();`

### Porta já em uso

Se a porta 8080 ou 5432 já estiverem em uso, altere no `docker-compose.yml`:

```yaml
ports:
  - "8081:80"  # Altere 8080 para 8081
```

### Permissões de arquivo

Se houver problemas de permissão:

```bash
# Ajustar permissões do storage
docker-compose exec app chmod -R 775 storage bootstrap/cache
```

---

## Desenvolvimento

### Workflow Recomendado

1. **Iniciar containers**: `docker-compose up -d`
2. **Instalar dependências**: `docker-compose exec app composer install`
3. **Configurar .env**: Copiar `.env.example` para `.env` e ajustar
4. **Gerar chave**: `docker-compose exec app php artisan key:generate`
5. **Executar migrations**: `docker-compose exec app php artisan migrate`
6. **Executar seeders**: `docker-compose exec app php artisan db:seed`
7. **Acessar aplicação**: http://localhost:8080

### Hot Reload

O código é montado como volume, então alterações no código são refletidas imediatamente. Apenas reinicie o PHP-FPM se necessário:

```bash
docker-compose restart app
```

