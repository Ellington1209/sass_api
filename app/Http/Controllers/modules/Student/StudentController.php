<?php

namespace App\Http\Controllers\modules\Student;

use App\Models\Student;
use App\Services\Student\StudentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class StudentController
{
    public function __construct(
        private StudentService $studentService
    ) {}

    /**
     * Lista todos os alunos
     */
    public function index(Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_id ?? $request->user()->tenantUsers()->first()?->tenant_id;
        
        if (!$tenantId) {
            return response()->json([
                'message' => 'Tenant não identificado',
            ], 400);
        }

        $filters = $request->only(['status', 'category', 'search']);
        $perPage = $request->input('per_page', 15); // Padrão: 15 itens por página
        $page = $request->input('page', 1); // Padrão: página 1
        
        // Valida per_page (máximo 100)
        $perPage = min(max((int) $perPage, 1), 100);
        $page = max((int) $page, 1);
        
        $result = $this->studentService->getAll($tenantId, $filters, $perPage, $page);

        return response()->json($result);
    }

    /**
     * Exibe um aluno específico
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $tenantId = $request->user()->tenant_id ?? $request->user()->tenantUsers()->first()?->tenant_id;
        
        if (!$tenantId) {
            return response()->json([
                'message' => 'Tenant não identificado',
            ], 400);
        }

        $student = $this->studentService->getById($id, $tenantId);

        if (!$student) {
            return response()->json([
                'message' => 'Aluno não encontrado',
            ], 404);
        }

        return response()->json($student);
    }

    /**
     * Cria um novo aluno
     */
    public function store(Request $request): JsonResponse
    {
        
        $tenantId = $request->user()->tenant_id ?? $request->user()->tenantUsers()->first()?->tenant_id;
        
        if (!$tenantId) {
            return response()->json([
                'message' => 'Tenant não identificado',
            ], 400);
        }

        // Validação customizada: photo_url pode ser arquivo ou string
        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'cpf' => 'required|string|size:14|unique:students,cpf',
            'rg' => 'nullable|string|max:20',
            'birth_date' => 'required|date',
            'phone' => 'nullable|string|max:20',
            'address_street' => 'nullable|string|max:255',
            'address_number' => 'nullable|string|max:20',
            'address_neighborhood' => 'nullable|string|max:255',
            'address_city' => 'nullable|string|max:255',
            'address_state' => 'nullable|string|size:2',
            'address_zip' => 'nullable|string|max:10',
            'category' => 'nullable|in:A,B,C,D,AB,AC,AD,AE',
            'status_students_id' => 'nullable|exists:status_students,id',
            'photo' => 'nullable|image|mimes:jpeg,jpg,png,gif,webp|max:5120', // 5MB max
        ];

        // Se photo_url for um arquivo, valida como imagem. Se for string, valida como string
        if ($request->hasFile('photo_url')) {
            $rules['photo_url'] = 'nullable|image|mimes:jpeg,jpg,png,gif,webp|max:5120';
        } else {
            $rules['photo_url'] = 'nullable|string|max:500';
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Erro na validação',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Prepara os dados validados incluindo o arquivo de foto se existir
        $data = $validator->validated();
        
        // Verifica se há arquivo de foto (pode vir como 'photo' ou 'photo_url')
        if ($request->hasFile('photo')) {
            $data['photo'] = $request->file('photo');
            Log::info('Arquivo photo recebido', ['name' => $data['photo']->getClientOriginalName(), 'size' => $data['photo']->getSize()]);
        } elseif ($request->hasFile('photo_url')) {
            $data['photo'] = $request->file('photo_url');
            Log::info('Arquivo photo_url recebido', ['name' => $data['photo']->getClientOriginalName(), 'size' => $data['photo']->getSize()]);
        } else {
            Log::info('Nenhum arquivo de foto recebido', ['has_photo' => $request->hasFile('photo'), 'has_photo_url' => $request->hasFile('photo_url'), 'all_files' => array_keys($request->allFiles())]);
        }
        
        $student = $this->studentService->create($tenantId, $data);

        return response()->json($student, 201);
    }

    /**
     * Atualiza um aluno
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $tenantId = $request->user()->tenant_id ?? $request->user()->tenantUsers()->first()?->tenant_id;
        
        if (!$tenantId) {
            return response()->json([
                'message' => 'Tenant não identificado',
            ], 400);
        }

        $student = \App\Models\Student::find($id);
        if (!$student) {
            return response()->json(['message' => 'Aluno não encontrado'], 404);
        }

        // O middleware HandlePutFormData deve ter processado os dados
        $requestData = $request->all();

        Log::info('Request recebido no update', [
            'method' => $request->method(),
            'content_type' => $request->header('Content-Type'),
            'is_json' => $request->isJson(),
            'request_data' => $requestData,
            'all' => $request->all(),
            'input' => $request->input(),
            'has_file' => $request->hasFile('photo'),
        ]);

        // Valida arquivo manualmente se existir (porque isValid() pode retornar false para arquivos criados manualmente)
        $photoFile = null;
        if ($request->hasFile('photo')) {
            $photoFile = $request->file('photo');
            // Validação manual do arquivo
            $allowedMimes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
            if (!in_array($photoFile->getMimeType(), $allowedMimes)) {
                return response()->json([
                    'message' => 'Erro na validação',
                    'errors' => ['photo' => ['Tipo de arquivo não permitido. Use apenas imagens (JPEG, PNG, GIF, WEBP).']],
                ], 422);
            }
            if ($photoFile->getSize() > 5 * 1024 * 1024) {
                return response()->json([
                    'message' => 'Erro na validação',
                    'errors' => ['photo' => ['Arquivo muito grande. Tamanho máximo: 5MB.']],
                ], 422);
            }
            // Remove photo do requestData para não validar novamente
            unset($requestData['photo']);
        }
        
        $rules = [
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $student->user_id,
            'cpf' => 'sometimes|string|size:14|unique:students,cpf,' . $id,
            'rg' => 'nullable|string|max:20',
            'birth_date' => 'sometimes|date',
            'phone' => 'nullable|string|max:20',
            'address_street' => 'nullable|string|max:255',
            'address_number' => 'nullable|string|max:20',
            'address_neighborhood' => 'nullable|string|max:255',
            'address_city' => 'nullable|string|max:255',
            'address_state' => 'nullable|string|size:2',
            'address_zip' => 'nullable|string|max:10',
            'category' => 'nullable|in:A,B,C,D,AB,AC,AD,AE',
            'status_students_id' => 'nullable|exists:status_students,id',
        ];

        $validator = Validator::make($requestData, $rules);

        if ($validator->fails()) {
            Log::error('Erro na validação', ['errors' => $validator->errors()->toArray()]);
            return response()->json([
                'message' => 'Erro na validação',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Prepara os dados validados incluindo o arquivo de foto se existir
        $data = $validator->validated();
        
        // Se validated() retornar vazio, usa requestData mas filtra apenas os campos permitidos
        if (empty($data)) {
            Log::warning('validated() retornou vazio, usando requestData filtrado', ['requestData' => $requestData]);
            $data = array_intersect_key($requestData, array_flip(array_keys($rules)));
            // Remove campos que não estão nas regras
            $data = array_filter($data, function($key) use ($rules) {
                return array_key_exists($key, $rules);
            }, ARRAY_FILTER_USE_KEY);
        }
        
        // Adiciona o arquivo de foto se foi validado manualmente
        if ($photoFile) {
            $data['photo'] = $photoFile;
            Log::info('Arquivo de foto recebido no update', [
                'name' => $data['photo']->getClientOriginalName(),
                'size' => $data['photo']->getSize(),
                'mime' => $data['photo']->getMimeType()
            ]);
        } elseif ($request->input('photo') === null) {
            // Se enviar null explicitamente, remove a foto
            $data['photo'] = null;
            Log::info('Remoção de foto solicitada');
        }
        
        Log::info('Dados validados recebidos no controller', [
            'id' => $id,
            'tenant_id' => $tenantId,
            'validated_data' => $data,
            'has_photo' => isset($data['photo']) && $data['photo'] instanceof UploadedFile
        ]);
        
        $student = $this->studentService->update($id, $tenantId, $data);
        
        Log::info('Resultado do update', ['student' => $student ? 'encontrado' : 'não encontrado']);

        if (!$student) {
            return response()->json([
                'message' => 'Aluno não encontrado',
            ], 404);
        }

        return response()->json($student);
    }

    /**
     * Exclui um ou vários alunos (soft delete)
     * Aceita: DELETE /students/{id} ou DELETE /students/batch com body {ids: [1, 2, 3]} ou DELETE /students com body {ids: [1, 2, 3]}
     */
    public function destroy(Request $request, int|string|null $id = null): JsonResponse
    {
        $tenantId = $request->user()->tenant_id ?? $request->user()->tenantUsers()->first()?->tenant_id;
        
        if (!$tenantId) {
            return response()->json([
                'message' => 'Tenant não identificado',
            ], 400);
        }

        // Determina os IDs a serem excluídos
        $ids = null;
        if ($id !== null && $id !== 'batch') {
            // ID na URL - converte string para int se necessário
            $ids = is_numeric($id) ? (int) $id : null;
            if ($ids === null) {
                return response()->json([
                    'message' => 'ID inválido na URL',
                ], 400);
            }
        } elseif ($request->has('ids') && is_array($request->ids)) {
            // Array de IDs no body
            $ids = $request->ids;
        } else {
            return response()->json([
                'message' => 'ID ou array de IDs não fornecido',
            ], 400);
        }

        $result = $this->studentService->delete($ids, $tenantId);

        // Se não encontrou nenhum aluno
        if (empty($result['deleted'])) {
            return response()->json([
                'message' => 'Nenhum aluno encontrado',
                'not_found' => $result['not_found'],
            ], 404);
        }

        $response = [
            'message' => count($result['deleted']) > 1 
                ? count($result['deleted']) . ' alunos excluídos com sucesso'
                : 'Aluno excluído com sucesso',
            'deleted' => $result['deleted'],
        ];

        if (!empty($result['not_found'])) {
            $response['not_found'] = $result['not_found'];
        }

        return response()->json($response);
    }

    /**
     * Adiciona um documento ao aluno
     */
    public function addDocument(Request $request, int $id): JsonResponse
    {
        $tenantId = $request->user()->tenant_id ?? $request->user()->tenantUsers()->first()?->tenant_id;
        
        if (!$tenantId) {
            return response()->json([
                'message' => 'Tenant não identificado',
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'type' => 'required|in:rg,cpf,comprovante,contrato,foto_3x4,assinatura,outros',
            'file_url' => 'required|string|max:500',
            'file_name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Erro na validação',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $document = $this->studentService->addDocument($id, $tenantId, $validator->validated());
            return response()->json($document, 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 404);
        }
    }

    /**
     * Remove um documento do aluno
     */
    public function removeDocument(Request $request, int $id, int $documentId): JsonResponse
    {
        $tenantId = $request->user()->tenant_id ?? $request->user()->tenantUsers()->first()?->tenant_id;
        
        if (!$tenantId) {
            return response()->json([
                'message' => 'Tenant não identificado',
            ], 400);
        }

        $deleted = $this->studentService->removeDocument($documentId, $tenantId);

        if (!$deleted) {
            return response()->json([
                'message' => 'Documento não encontrado',
            ], 404);
        }

        return response()->json([
            'message' => 'Documento removido com sucesso',
        ]);
    }

    /**
     * Adiciona uma observação ao aluno
     */
    public function addNote(Request $request, int $id): JsonResponse
    {
        $tenantId = $request->user()->tenant_id ?? $request->user()->tenantUsers()->first()?->tenant_id;
        
        if (!$tenantId) {
            return response()->json([
                'message' => 'Tenant não identificado',
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'note' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Erro na validação',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $note = $this->studentService->addNote(
                $id,
                $tenantId,
                $request->user()->id,
                $validator->validated()['note']
            );
            return response()->json($note, 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 404);
        }
    }

    /**
     * Remove uma observação do aluno
     */
    public function removeNote(Request $request, int $id, int $noteId): JsonResponse
    {
        $tenantId = $request->user()->tenant_id ?? $request->user()->tenantUsers()->first()?->tenant_id;
        
        if (!$tenantId) {
            return response()->json([
                'message' => 'Tenant não identificado',
            ], 400);
        }

        $deleted = $this->studentService->removeNote($noteId, $tenantId);

        if (!$deleted) {
            return response()->json([
                'message' => 'Observação não encontrada',
            ], 404);
        }

        return response()->json([
            'message' => 'Observação removida com sucesso',
        ]);
    }
}

