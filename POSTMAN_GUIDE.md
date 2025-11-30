# Guia de Testes no Postman

## 📋 Configuração Inicial

### 1. Criar Environment (Opcional mas Recomendado)

1. Clique em **"Environments"** no menu lateral
2. Clique em **"+"** para criar novo environment
3. Nome: `Laravel SaaS Local`
4. Adicione as variáveis:
   - `base_url`: `http://localhost:8080`
   - `token`: (deixe vazio inicialmente)

### 2. Configurar Collection (Opcional)

1. Crie uma nova Collection chamada "Laravel SaaS Auth"
2. Configure a Collection para usar o environment criado
3. Na aba "Variables" da Collection, adicione:
   - `base_url`: `http://localhost:8080`

---

## 🔐 Endpoint 1: Login

### Configuração da Requisição

**URL:** `{{base_url}}/api/auth/login`  
**Método:** `POST`

### Headers
```
Content-Type: application/json
Accept: application/json
```

### Body (raw JSON)
```json
{
  "email": "ellington1209@gmail.com",
  "password": "Tonemara89"
}
```

### Script para Salvar Token Automaticamente (Tests Tab)

Cole este código na aba **"Tests"** da requisição de login:

```javascript
// Verifica se o login foi bem-sucedido
if (pm.response.code === 200) {
    const response = pm.response.json();
    
    // Salva o token no environment
    if (response.token) {
        pm.environment.set("token", response.token);
        console.log("Token salvo:", response.token);
    }
    
    // Salva também na collection variable (backup)
    pm.collectionVariables.set("token", response.token);
}
```

### Resposta Esperada (200 OK)
```json
{
  "user": {
    "id": 1,
    "name": "Ellington Machado de Paula",
    "email": "ellington1209@gmail.com",
    "tenant_id": null,
    "is_super_admin": true
  },
  "permissions": [],
  "modules": ["Auth"],
  "token": "4|b7t7fT0KliKh9WIZJVpmqbB8yNkTKVtcyDOirQ161ba06398"
}
```

---

## 👤 Endpoint 2: Me (Dados do Usuário)

### Configuração da Requisição

**URL:** `{{base_url}}/api/auth/me`  
**Método:** `GET`

### Headers
```
Authorization: Bearer {{token}}
Accept: application/json
```

**Importante:** O token será preenchido automaticamente se você configurou o script no login!

### Resposta Esperada (200 OK)
```json
{
  "user": {
    "id": 1,
    "name": "Ellington Machado de Paula",
    "email": "ellington1209@gmail.com",
    "tenant_id": null,
    "is_super_admin": true
  },
  "permissions": [],
  "modules": ["Auth"]
}
```

### Teste de Validação (Tests Tab)

```javascript
pm.test("Status code is 200", function () {
    pm.response.to.have.status(200);
});

pm.test("Response has user data", function () {
    const response = pm.response.json();
    pm.expect(response).to.have.property('user');
    pm.expect(response.user).to.have.property('email');
});
```

---

## 🚪 Endpoint 3: Logout

### Configuração da Requisição

**URL:** `{{base_url}}/api/auth/logout`  
**Método:** `POST`

### Headers
```
Authorization: Bearer {{token}}
Accept: application/json
```

### Body
Deixe vazio ou envie `{}`

### Resposta Esperada (200 OK)
```json
{
  "message": "Logged out successfully"
}
```

### Teste de Validação (Tests Tab)

```javascript
pm.test("Status code is 200", function () {
    pm.response.to.have.status(200);
});

pm.test("Logout successful", function () {
    const response = pm.response.json();
    pm.expect(response.message).to.eql("Logged out successfully");
});

// Limpa o token após logout
pm.environment.set("token", "");
```

---

## ⚠️ Teste de Erro: Login com Credenciais Inválidas

### Configuração da Requisição

**URL:** `{{base_url}}/api/auth/login`  
**Método:** `POST`

### Body (raw JSON)
```json
{
  "email": "email@errado.com",
  "password": "senhaerrada"
}
```

### Resposta Esperada (401 Unauthorized)
```json
{
  "message": "Invalid credentials"
}
```

---

## ⚠️ Teste de Erro: Acesso sem Token

### Configuração da Requisição

**URL:** `{{base_url}}/api/auth/me`  
**Método:** `GET`

### Headers
```
Accept: application/json
```
(Não inclua o Authorization header)

### Resposta Esperada (401 Unauthorized)
```json
{
  "message": "Unauthenticated."
}
```

---

## 📝 Checklist de Testes

- [ ] Login com credenciais válidas retorna 200 e token
- [ ] Token é salvo automaticamente no environment
- [ ] `/me` retorna dados do usuário com token válido
- [ ] `/logout` revoga o token com sucesso
- [ ] Login com credenciais inválidas retorna 401
- [ ] Acesso a `/me` sem token retorna 401
- [ ] Acesso a `/me` com token inválido retorna 401
- [ ] Acesso a `/me` após logout retorna 401

---

## 🎯 Dicas Extras

### 1. Pre-request Script (Opcional)

Se quiser garantir que sempre tenha um token válido antes de fazer requisições autenticadas, adicione este script na aba **"Pre-request Script"** da Collection:

```javascript
// Verifica se o token existe, se não, faz login automaticamente
if (!pm.environment.get("token")) {
    pm.sendRequest({
        url: pm.environment.get("base_url") + "/api/auth/login",
        method: 'POST',
        header: {
            'Content-Type': 'application/json',
        },
        body: {
            mode: 'raw',
            raw: JSON.stringify({
                email: "ellington1209@gmail.com",
                password: "Tonemara89"
            })
        }
    }, function (err, res) {
        if (res.json().token) {
            pm.environment.set("token", res.json().token);
        }
    });
}
```

### 2. Organização de Pastas

Organize suas requisições assim:
```
📁 Laravel SaaS Auth
  📁 Auth
    📄 Login
    📄 Me
    📄 Logout
  📁 Errors
    📄 Login - Invalid Credentials
    📄 Me - Unauthenticated
```

### 3. Exportar Collection

1. Clique nos 3 pontos da Collection
2. Selecione **"Export"**
3. Salve o arquivo JSON
4. Compartilhe com sua equipe!

---

## 🔗 URLs Completas (sem variáveis)

- Login: `http://localhost:8080/api/auth/login`
- Me: `http://localhost:8080/api/auth/me`
- Logout: `http://localhost:8080/api/auth/logout`

---

## 📞 Credenciais de Teste

- **Email:** `ellington1209@gmail.com`
- **Senha:** `Tonemara89`
- **Tipo:** Super Admin

