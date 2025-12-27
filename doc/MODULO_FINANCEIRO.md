# 💰 MÓDULO FINANCEIRO - SaaS Multi-Tenant

## 📋 Visão Geral

Módulo financeiro centralizado, flexível e escalável para gerenciar transações financeiras, comissões e configurações em um sistema multi-tenant que atende diferentes tipos de negócios (autoescola, barbearia, salão, clínica, etc).

## 🧱 Arquitetura

### Princípios Fundamentais

1. **Single Source of Truth**: Todo dinheiro que entra ou sai passa pela tabela `financial_transactions`
2. **Eventos Financeiros**: Agenda, aulas e serviços não são financeiros, apenas **geram eventos financeiros**
3. **Comissões Gravadas**: Comissão sempre é gravada, nunca calculada apenas em relatório
4. **Configurável por Tenant**: Tipos de gastos/entradas configuráveis por tenant
5. **Sem ENUM Engessado**: Tipos de origem são configuráveis, não hard-coded

## 📦 Estrutura de Tabelas

### 1. `financial_origins`
Define a origem ou motivo do lançamento financeiro.

**Campos:**
- `id` - Identificador único
- `tenant_id` - FK para tenants
- `name` - Nome da origem (Ex: Aula prática, Corte, Taxa Detran)
- `origin_type` - ENUM('OPERATIONAL','MANUAL')
- `active` - Ativo/Inativo
- `timestamps` + `soft_deletes`

**Regras:**
- `OPERATIONAL`: gerado pelo sistema (aula, serviço, atendimento)
- `MANUAL`: lançado manualmente pelo tenant (despesa ou entrada avulsa)
- Tenant pode criar quantas origens quiser

### 2. `financial_categories`
Agrupamento contábil para relatórios.

**Campos:**
- `id` - Identificador único
- `tenant_id` - FK para tenants
- `name` - Nome da categoria (Ex: Serviços, Insumos, Impostos)
- `type` - ENUM('IN','OUT')
- `active` - Ativo/Inativo
- `timestamps` + `soft_deletes`

### 3. `payment_methods`
Forma de pagamento.

**Campos:**
- `id` - Identificador único
- `tenant_id` - FK para tenants
- `name` - Nome (Pix, Dinheiro, Crédito, Débito)
- `active` - Ativo/Inativo
- `timestamps` + `soft_deletes`

### 4. `financial_transactions` ⭐ (NÚCLEO DO SISTEMA)
Todas as transações financeiras do sistema.

**Campos:**
- `id` - Identificador único
- `tenant_id` - FK para tenants
- `type` - ENUM('IN','OUT')
- `amount` - DECIMAL(10,2)
- `description` - Descrição
- `origin_id` - FK financial_origins
- `category_id` - FK financial_categories
- `payment_method_id` - FK payment_methods
- `reference_type` - Tipo da referência (appointment, service, manual, etc)
- `reference_id` - ID da referência
- `service_price_id` - FK service_prices (opcional)
- `status` - ENUM('PENDING','CONFIRMED','CANCELLED')
- `occurred_at` - Data/hora da ocorrência
- `created_by` - FK users
- `timestamps` + `soft_deletes`

**Regras Importantes:**
- Todo lançamento financeiro está aqui
- `reference_id` só é preenchido quando existir um evento operacional
- Lançamentos manuais têm `reference_id = NULL`
- Transações canceladas não são deletadas, apenas marcadas como `CANCELLED`

### 5. `commissions`
Controle de comissão por profissional.

**Campos:**
- `id` - Identificador único
- `tenant_id` - FK para tenants
- `provider_id` - FK providers
- `transaction_id` - FK financial_transactions
- `origin_id` - FK financial_origins
- `reference_type` - Tipo da referência
- `reference_id` - ID da referência
- `base_amount` - Valor base
- `commission_rate` - Taxa de comissão (%)
- `commission_amount` - Valor da comissão
- `status` - ENUM('PENDING','PAID','CANCELLED')
- `paid_at` - Data do pagamento
- `payment_transaction_id` - FK financial_transactions (transação de pagamento)
- `timestamps` + `soft_deletes`

**Regras:**
- Comissão nunca é apagada, apenas marcada como cancelada
- Pagamento altera status e cria transação de saída
- Vincula à transação original para rastreabilidade

### 6. `provider_commission_configs`
Configuração de comissões por profissional e origem.

**Campos:**
- `id` - Identificador único
- `tenant_id` - FK para tenants
- `provider_id` - FK providers
- `origin_id` - FK financial_origins (NULL = aplica para todas)
- `commission_rate` - Taxa de comissão (%)
- `active` - Ativo/Inativo
- `timestamps` + `soft_deletes`
- **UNIQUE**: (tenant_id, provider_id, origin_id)

## 🔁 Fluxos de Negócio

### ✅ Serviço / Aula (Automático)

```php
// Quando um agendamento é finalizado
1. Atualiza status do agendamento
2. Cria financial_transaction (IN)
3. Busca config de comissão
4. Cria commission (se aplicável)
```

### ✅ Entrada Manual

```php
// Ex: ajuste de caixa, venda avulsa
type = IN
origin_type = MANUAL
reference_id = NULL
```

### ✅ Saída Manual

```php
// Ex: aluguel, mercado, lâminas, taxa do detran
type = OUT
origin_type = MANUAL
reference_id = NULL
```

### ✅ Pagamento de Comissão

```php
1. Busca comissão pendente
2. Cria financial_transaction (OUT) para pagamento
3. Atualiza comissão: status = PAID, paid_at = now()
4. Vincula payment_transaction_id
```

## 👀 Visões do Sistema

### 🔧 Tenant (Dono)
- Entradas e saídas
- Lucro real
- Despesas por origem
- Comissões pendentes e pagas
- Fluxo de caixa mensal
- Dashboard financeiro

**Permissões:**
- `financeiro.view` - Dashboard
- `financeiro.transactions.*` - Gerenciar transações
- `financeiro.commissions.*` - Gerenciar comissões
- `financeiro.reports.*` - Relatórios
- `financeiro.*_configs.*` - Configurações

### 👨‍💼 Profissional (Provider)
- Serviços realizados
- Valor total gerado
- Comissão pendente
- Comissão paga
- Histórico de comissões

**Permissões:**
- `financeiro.commissions.view` (limitado ao próprio provider)

### 👤 Cliente Final
- Histórico de pagamentos
- Total pago no mês
- Valores pendentes

## 🛣️ Rotas da API

### Dashboard e Relatórios

```
GET /api/financial/reports/dashboard?start_date=&end_date=
GET /api/financial/reports/cash-flow?year=2025&month=1
GET /api/financial/reports/commissions?provider_id=&status=&start_date=&end_date=
```

### Transações

```
GET    /api/financial/transactions
GET    /api/financial/transactions/{id}
POST   /api/financial/transactions
PUT    /api/financial/transactions/{id}
PATCH  /api/financial/transactions/{id}
POST   /api/financial/transactions/{id}/cancel
DELETE /api/financial/transactions/{id}
```

### Comissões

```
GET  /api/financial/commissions
GET  /api/financial/commissions/{id}
POST /api/financial/commissions/{id}/pay
POST /api/financial/commissions/{id}/cancel
GET  /api/financial/commissions/totals/by-provider
```

### Configurações - Origens

```
GET    /api/financial/origins
POST   /api/financial/origins
PUT    /api/financial/origins/{id}
DELETE /api/financial/origins/{id}
```

### Configurações - Categorias

```
GET    /api/financial/categories
POST   /api/financial/categories
PUT    /api/financial/categories/{id}
DELETE /api/financial/categories/{id}
```

### Configurações - Métodos de Pagamento

```
GET    /api/financial/payment-methods
POST   /api/financial/payment-methods
PUT    /api/financial/payment-methods/{id}
DELETE /api/financial/payment-methods/{id}
```

### Configurações - Comissões

```
GET    /api/financial/commission-configs
POST   /api/financial/commission-configs
PUT    /api/financial/commission-configs/{id}
DELETE /api/financial/commission-configs/{id}
```

## 🔐 Permissões

### Dashboard e Relatórios
- `financeiro.view` - Visualizar dashboard
- `financeiro.reports.view` - Ver relatórios
- `financeiro.reports.export` - Exportar relatórios

### Transações
- `financeiro.transactions.view` - Visualizar
- `financeiro.transactions.create` - Criar
- `financeiro.transactions.edit` - Editar
- `financeiro.transactions.delete` - Deletar
- `financeiro.transactions.cancel` - Cancelar

### Comissões
- `financeiro.commissions.view` - Visualizar
- `financeiro.commissions.pay` - Pagar
- `financeiro.commissions.cancel` - Cancelar

### Configurações
- `financeiro.origins.*` - Origens
- `financeiro.categories.*` - Categorias
- `financeiro.payment_methods.*` - Métodos de Pagamento
- `financeiro.commission_configs.*` - Configurações de Comissão

### Permissão Completa
- `financeiro.manage` - Acesso total ao módulo

## 📊 Exemplos de Uso

### Criar Transação Manual

```json
POST /api/financial/transactions
{
  "type": "OUT",
  "amount": 150.00,
  "description": "Compra de produtos de limpeza",
  "origin_id": 5,
  "category_id": 3,
  "payment_method_id": 1,
  "status": "CONFIRMED",
  "occurred_at": "2025-12-27 14:30:00"
}
```

### Configurar Comissão de Provider

```json
POST /api/financial/commission-configs
{
  "provider_id": 10,
  "origin_id": 2,
  "commission_rate": 40.00,
  "active": true
}
```

### Pagar Comissão

```json
POST /api/financial/commissions/{id}/pay
{
  "origin_id": 8,
  "category_id": 4,
  "payment_method_id": 1,
  "occurred_at": "2025-12-27 15:00:00"
}
```

## 🚀 Como Usar

### 1. Executar Migrations

```bash
php artisan migrate
```

### 2. Executar Seeders (para criar permissões)

```bash
php artisan db:seed --class=PermissionSeeder
```

### 3. Configurar Origens Financeiras

Exemplo de origens para uma barbearia:
- Corte de Cabelo (OPERATIONAL)
- Barba (OPERATIONAL)
- Produtos (MANUAL)
- Aluguel (MANUAL)
- Energia (MANUAL)

### 4. Configurar Categorias

Exemplo de categorias:
- Serviços (IN)
- Produtos (IN)
- Despesas Fixas (OUT)
- Despesas Variáveis (OUT)
- Impostos (OUT)

### 5. Configurar Métodos de Pagamento

- Dinheiro
- PIX
- Crédito
- Débito
- Transferência

### 6. Configurar Comissões

```
Provider: João Silva
Origem: Corte de Cabelo
Taxa: 40%
```

## ✨ Benefícios

✅ **Único e Centralizado**: Uma única fonte de verdade para finanças  
✅ **Auditável**: Histórico completo com soft deletes  
✅ **Flexível**: Configurável por tenant  
✅ **Escalável**: Pronto para qualquer tipo de negócio  
✅ **Rastreável**: Vincula transações com eventos operacionais  
✅ **Multi-tenant**: Isolamento total por tenant  
✅ **Sem Refatoração**: Design pensado para longo prazo  

## 🎯 Próximos Passos

1. Integrar com AgendaService para criar transações automáticas
2. Criar seeders com dados exemplo para cada tipo de negócio
3. Implementar exportação de relatórios (PDF/Excel)
4. Criar notificações de comissões pendentes
5. Dashboard visual com gráficos (Chart.js)

---

**Criado em:** 27/12/2025  
**Versão:** 1.0.0  
**Status:** ✅ Pronto para uso

