# 📘 Permission System – Documentação Oficial

Sistema de permissões para SaaS multi-tenant.  
Permite que cada tenant gerencie usuários, módulos e permissões individuais.

---

## 🔎 Visão Geral

O sistema é baseado em três entidades:

1. **Modules** – áreas do sistema (Ex: Alunos, Agenda, Financeiro)
2. **Permissions** – ações permitidas dentro de cada módulo
3. **User Permissions** – permissões atribuídas a cada usuário

Cada tenant possui seus módulos habilitados em `tenant_modules`, e somente permissões desses módulos podem ser atribuídas.

---

# 🧱 Estrutura das Tabelas

## ▸ modules

| Campo       | Tipo   | Descrição                           |
|-------------|--------|-------------------------------------|
| id          | bigint | Identificador                       |
| key         | string | Identificador único (`alunos`)      |
| name        | string | Nome exibido                        |
| description | string | Descrição (opcional)                |

---

## ▸ permissions

| Campo       | Tipo   | Descrição                                |
|-------------|--------|--------------------------------------------|
| id          | bigint | Identificador                              |
| module_id   | FK     | Referência ao módulo                       |
| key         | string | Chave da permissão (`alunos.view`)         |
| label       | string | Nome exibido                               |
| description | string | Descrição (opcional)                       |

---

## ▸ tenant_modules

| Campo     | Tipo   |
|-----------|--------|
| id        | bigint |
| tenant_id | FK     |
| module_id | FK     |

---

## ▸ user_permissions

| Campo        | Tipo   |
|--------------|--------|
| id           | bigint |
| user_id      | FK     |
| permission_id| FK     |

---

# 🎯 Regras

### ✔ Super Admin
- Tem acesso total, ignora sistema de permissões.

### ✔ Tenants
- Só podem atribuir permissões de módulos habilitados em `tenant_modules`.

### ✔ Usuários
- Recebem permissões via `user_permissions`.

---

# 🔌 Endpoints

## ▸ Listar permissões do tenant + permissões do usuário

### Resposta
```json
{
  "modules": [
    {
      "id": 1,
      "name": "Alunos",
      "key": "alunos",
      "permissions": [
        { "id": 10, "key": "alunos.view", "label": "Ver alunos" },
        { "id": 11, "key": "alunos.create", "label": "Criar alunos" }
      ]
    }
  ],
  "user_permissions": [10]
}
