# Documentação das Rotas de WhatsApp - API



---

## 1. LISTAR INSTÂNCIAS

**Método:** `GET`  
**URL:** `/api/whatsapp/instances`  
**Permissão necessária:** `whatsapp.instances.view`

### Resposta de Sucesso (200)
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "evolution_id": "nome-da-instancia",
      "tenant_id": 1,
      "name": "nome-da-instancia",
      "status": "connected",
      "created_at": "2025-12-20T10:00:00.000000Z",
      "updated_at": "2025-12-20T10:00:00.000000Z"
    }
  ]
}
```

### Resposta de Erro (400)
```json
{
  "success": false,
  "message": "Tenant não identificado"
}
```

---

## 2. CRIAR INSTÂNCIA

**Método:** `POST`  
**URL:** `/api/whatsapp/instances`  
**Permissão necessária:** `whatsapp.instances.create`

### Body
```json
{
  "instanceName": "nome-da-instancia",
  "number": "5511999999999"
}
```

**Campos:**
- `instanceName` (obrigatório): Nome da instância
- `number` (opcional): Número de telefone

### Resposta de Sucesso (201)
```json
{
  "success": true,
  "data": {
    "instance": {
      "id": 1,
      "evolution_id": "nome-da-instancia",
      "tenant_id": 1,
      "name": "nome-da-instancia",
      "status": "disconnected",
      "created_at": "2025-12-20T10:00:00.000000Z",
      "updated_at": "2025-12-20T10:00:00.000000Z"
    },
    "qrcode": "data:image/png;base64,iVBORw0KGgoAAAANS..."
  }
}
```

### Resposta de Erro - Validação (422)
```json
{
  "success": false,
  "message": "Erro na validação",
  "errors": {
    "instanceName": ["O campo instanceName é obrigatório."]
  }
}
```

### Resposta de Erro (400)
```json
{
  "success": false,
  "message": "Erro ao criar instância",
  "error": {...}
}
```

### Resposta de Erro - Super Admin (403)
```json
{
  "success": false,
  "message": "Super admin não pode criar instâncias"
}
```

---

## 3. ENVIAR MENSAGEM

**Método:** `POST`  
**URL:** `/api/whatsapp/instances/{id}/send`  
**Permissão necessária:** `whatsapp.instances.send`

**Parâmetro na URL:**
- `{id}` = ID da instância no banco de dados

### Body
```json
{
  "number": "5511999999999",
  "text": "Mensagem a ser enviada"
}
```

**Campos:**
- `number` (obrigatório): Número de telefone do destinatário
- `text` (obrigatório): Texto da mensagem

### Resposta de Sucesso (200)
```json
{
  "success": true,
  "data": {...}
}
```

### Resposta de Erro - Instância não encontrada (404)
```json
{
  "success": false,
  "message": "Instância não encontrada ou não pertence ao seu tenant"
}
```

### Resposta de Erro - Validação (422)
```json
{
  "success": false,
  "message": "Erro na validação",
  "errors": {
    "number": ["O campo number é obrigatório."],
    "text": ["O campo text é obrigatório."]
  }
}
```

### Resposta de Erro (400)
```json
{
  "success": false,
  "message": "Erro ao enviar mensagem",
  "error": {...}
}
```

---

## OBSERVAÇÕES IMPORTANTES

- Todas as rotas filtram automaticamente por tenant do usuário logado
- Super admin não pode criar instâncias (retorna 403)
- O campo `status` pode ser: `"connected"` ou `"disconnected"`
- O QR code é retornado apenas na criação da instância (campo `qrcode` pode ser `null`)
- O `evolution_id` é o nome/identificador da instância no Evolution API
- A validação de tenant é feita automaticamente em todas as rotas
- Usuários só podem ver e usar instâncias do seu próprio tenant

---

## EXEMPLO DE USO NO FRONTEND

### Listar Instâncias
```javascript
const response = await fetch('/api/whatsapp/instances', {
  method: 'GET',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json'
  }
});
const data = await response.json();
```

### Criar Instância
```javascript
const response = await fetch('/api/whatsapp/instances', {
  method: 'POST',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    instanceName: 'minha-instancia',
    number: '5511999999999' // opcional
  })
});
const data = await response.json();

// Se tiver QR code, exibir para escanear
if (data.data.qrcode) {
  // Exibir QR code em uma imagem
  const qrImage = document.createElement('img');
  qrImage.src = data.data.qrcode;
}
```

### Enviar Mensagem
```javascript
const response = await fetch(`/api/whatsapp/instances/${instanceId}/send`, {
  method: 'POST',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    number: '5511999999999',
    text: 'Olá, esta é uma mensagem de teste!'
  })
});
const data = await response.json();
```

