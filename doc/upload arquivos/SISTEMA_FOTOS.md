# Sistema de Gerenciamento de Fotos - Documentação

## Visão Geral

O sistema de fotos foi implementado para funcionar com Backblaze B2 (S3-compatible) em um ambiente Laravel multi-tenant. As fotos são armazenadas no B2 e servidas através de endpoints da API.

## Componentes Principais

### 1. Middleware: HandlePutFormData

**Localização:** `app/Http/Middleware/HandlePutFormData.php`

**Problema resolvido:** O Laravel não processa `multipart/form-data` em requisições PUT/PATCH automaticamente. Este middleware faz o parse manual.

**Como funciona:**
- Detecta requisições PUT/PATCH com `multipart/form-data`
- Faz parse manual do conteúdo usando o boundary
- Separa dados de texto e arquivos
- Cria arquivos temporários para uploads
- Converte arquivos em objetos `UploadedFile` do Laravel
- Adiciona ao `$request->files` para validação

**Registrado em:** `bootstrap/app.php` (globalmente)

### 2. FileService

**Localização:** `app/Services/FileService.php`

**Responsabilidades:**
- Upload de arquivos para B2
- Organização automática por tenant e categoria
- Criação de registros na tabela `files`
- Geração de URLs temporárias
- Servir arquivos via API

**Métodos principais:**

```php
// Upload
upload(UploadedFile $file, int $tenantId, string $type, ?int $userId): File

// Delete
delete(string $path): bool

// URL temporária
url(string $path, int $minutes = 60): string

// Servir arquivo (response HTTP)
response(string $path, ?string $mime = null): Response
```

**Organização de arquivos:**
- `tenants/{tenant_id}/avatars/{uuid}.ext` - Fotos de perfil
- `tenants/{tenant_id}/documentos/{uuid}.ext` - Documentos
- `tenants/{tenant_id}/anexos/{uuid}.ext` - Anexos
- `tenants/{tenant_id}/uploads/{uuid}.ext` - Uploads genéricos

### 3. StudentService - Upload de Fotos

**Localização:** `app/Services/Student/StudentService.php`

**Método:** `uploadPhoto()`

**Validações:**
- Tipos permitidos: JPEG, PNG, GIF, WEBP
- Tamanho máximo: 5MB

**Fluxo:**
1. Valida tipo e tamanho
2. Chama `FileService->upload()` com tipo `avatar`
3. Retorna o path do arquivo
4. Path é salvo no campo `photo_url` do aluno

**Método:** `deletePhoto()`

**Fluxo:**
1. Busca o arquivo na tabela `files` pelo path
2. Remove do B2 via `FileService->delete()`
3. Remove registro do banco

### 4. Rotas de Arquivos

**Localização:** `routes/api.php`

```php
// Upload de arquivo
POST /api/files/upload
- Requer: files.upload
- Body: file (multipart), type (avatar|documento|anexo|upload)

// Deletar arquivo
DELETE /api/files/{id}/delete
- Requer: files.delete

// Gerar URL temporária
GET /api/files/{id}/url?minutes=60
- Requer: files.download

// Servir arquivo público (apenas avatares)
GET /api/files/public/{path}
- Público (sem autenticação)
- Apenas arquivos tipo 'avatar'
```

### 5. Formatação de Resposta

**No StudentService->formatStudent():**

```php
'photo_url' => $student->photo_url ? (
    str_starts_with($student->photo_url, 'tenants/') 
        ? url('/api/files/public/' . urlencode($student->photo_url))
        : $student->photo_url
) : null
```

**Como funciona:**
- Se `photo_url` começa com `tenants/`, gera URL da API
- Se não, retorna a URL original (compatibilidade)
- URL gerada: `http://dominio/api/files/public/tenants/1/avatars/uuid.jpg`

## Fluxo Completo

### Criar Aluno com Foto

1. **Frontend envia:**
   - `POST /api/students`
   - `multipart/form-data`
   - Campo `photo` com arquivo

2. **Backend processa:**
   - Valida dados e arquivo
   - Inicia transação de banco
   - Cria usuário
   - Cria permissões (files.upload, files.view, files.download)
   - Faz upload da foto via `FileService`
   - Salva path no `photo_url` do aluno
   - Commita transação

3. **Resposta:**
   - Retorna aluno com `photo_url` formatado
   - URL: `/api/files/public/tenants/1/avatars/uuid.jpg`

### Atualizar Aluno com Foto

1. **Frontend envia:**
   - `PUT /api/students/{id}`
   - `multipart/form-data`
   - Campo `photo` com arquivo

2. **Middleware processa:**
   - `HandlePutFormData` faz parse do multipart
   - Cria `UploadedFile` e adiciona ao request

3. **Backend processa:**
   - Valida dados e arquivo manualmente
   - Inicia transação
   - Remove foto antiga (se existir)
   - Faz upload da nova foto
   - Atualiza `photo_url` do aluno
   - Commita transação

4. **Resposta:**
   - Retorna aluno atualizado com nova `photo_url`

### Visualizar Foto

1. **Frontend acessa:**
   - URL: `http://dominio/api/files/public/tenants/1/avatars/uuid.jpg`

2. **Backend processa:**
   - `FileController->showPublic()` valida:
     - Arquivo existe na tabela `files`
     - Tipo é `avatar`
   - Busca arquivo do B2
   - Retorna com headers corretos (Content-Type, etc.)

## Transações de Banco

**Todas as operações estão dentro de transações:**

- **Criar aluno:** Se upload falhar, nada é salvo
- **Atualizar aluno:** Se upload falhar, rollback completo

**Implementação:**
```php
return DB::transaction(function () {
    // ... operações ...
});
```

## Permissões

**Permissões atribuídas automaticamente ao criar aluno:**
- `files.upload` - Fazer upload
- `files.view` - Visualizar arquivos
- `files.download` - Baixar arquivos (URL temporária)
- `students.upload_document` - Upload de documentos

**Nota:** Alunos NÃO recebem `files.delete` por padrão.

## Configuração

**Arquivo:** `config/filesystems.php`

```php
'b2' => [
    'driver' => 's3',
    'key' => env('AWS_ACCESS_KEY_ID'),
    'secret' => env('AWS_SECRET_ACCESS_KEY'),
    'region' => env('AWS_DEFAULT_REGION'),
    'bucket' => env('AWS_BUCKET'),
    'endpoint' => env('AWS_ENDPOINT'),
    'use_path_style_endpoint' => true,
    'throw' => true,
],
```

**Variáveis de ambiente (.env):**
```
FILESYSTEM_DISK=b2
AWS_ACCESS_KEY_ID=sua_application_key_id
AWS_SECRET_ACCESS_KEY=sua_application_key
AWS_DEFAULT_REGION=us-east-005
AWS_BUCKET=nome-do-bucket
AWS_ENDPOINT=https://s3.us-east-005.backblazeb2.com
```

## Como Usar no Futuro

### Para adicionar upload de foto em outro módulo:

1. **No Service:**
```php
use App\Services\FileService;

// No construtor
public function __construct(private FileService $fileService) {}

// No método de criação/atualização
if (isset($data['photo']) && $data['photo'] instanceof UploadedFile) {
    $fileRecord = $this->fileService->upload(
        $data['photo'],
        $tenantId,
        'avatar', // ou outro tipo
        $userId
    );
    $data['photo_url'] = $fileRecord->path;
    unset($data['photo']);
}
```

2. **No Controller:**
```php
// Validação manual do arquivo (se PUT/PATCH)
if ($request->hasFile('photo')) {
    $photoFile = $request->file('photo');
    // Validação manual aqui
    $data['photo'] = $photoFile;
}
```

3. **Na formatação de resposta:**
```php
'photo_url' => $model->photo_url ? (
    str_starts_with($model->photo_url, 'tenants/') 
        ? url('/api/files/public/' . urlencode($model->photo_url))
        : $model->photo_url
) : null
```

### Para usar em outros tipos de arquivo:

```php
// Documento
$fileRecord = $this->fileService->upload($file, $tenantId, 'documento', $userId);

// Anexo
$fileRecord = $this->fileService->upload($file, $tenantId, 'anexo', $userId);

// Upload genérico
$fileRecord = $this->fileService->upload($file, $tenantId, 'upload', $userId);
```

## Pontos Importantes

1. **PUT/PATCH com multipart:** Sempre use o middleware `HandlePutFormData` (já registrado globalmente)

2. **Validação de arquivo:** Em PUT/PATCH, valide manualmente antes do validador do Laravel

3. **Transações:** Sempre use `DB::transaction()` quando upload faz parte de operação maior

4. **Path vs URL:** 
   - Salve o **path** no banco (ex: `tenants/1/avatars/uuid.jpg`)
   - Gere a **URL** na formatação de resposta

5. **Permissões:** Alunos recebem upload/view/download, mas NÃO delete

6. **Limpeza:** Arquivos temporários são gerenciados automaticamente pelo PHP

## Troubleshooting

**Arquivo não aparece no B2:**
- Verifique credenciais no `.env`
- Verifique permissões da Application Key no Backblaze
- Verifique logs em `storage/logs/laravel.log`

**Erro "File does not exist":**
- Arquivo temporário pode ter sido deletado
- O `FileService` tenta ler de múltiplas formas automaticamente

**PUT não funciona:**
- Verifique se o middleware `HandlePutFormData` está registrado
- Verifique Content-Type: deve ser `multipart/form-data`

**Foto não aparece:**
- Verifique se o path está salvo corretamente no banco
- Verifique se a rota `/api/files/public/{path}` está acessível
- Verifique permissões do arquivo no B2

