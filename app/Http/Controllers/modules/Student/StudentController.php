<?php

namespace App\Http\Controllers\modules\Student;

use App\Services\Student\StudentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
        if ($request->hasFile('photo')) {
            $data['photo'] = $request->file('photo');
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

        $validator = Validator::make($request->all(), [
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
            'photo' => 'nullable|image|mimes:jpeg,jpg,png,gif,webp|max:5120', // 5MB max
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Erro na validação',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Prepara os dados validados incluindo o arquivo de foto se existir
        $data = $validator->validated();
        if ($request->hasFile('photo')) {
            $data['photo'] = $request->file('photo');
        } elseif ($request->input('photo') === null) {
            // Se enviar null explicitamente, remove a foto
            $data['photo'] = null;
        }
        
        $student = $this->studentService->update($id, $tenantId, $data);

        if (!$student) {
            return response()->json([
                'message' => 'Aluno não encontrado',
            ], 404);
        }

        return response()->json($student);
    }

    /**
     * Exclui um aluno
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $tenantId = $request->user()->tenant_id ?? $request->user()->tenantUsers()->first()?->tenant_id;
        
        if (!$tenantId) {
            return response()->json([
                'message' => 'Tenant não identificado',
            ], 400);
        }

        $deleted = $this->studentService->delete($id, $tenantId);

        if (!$deleted) {
            return response()->json([
                'message' => 'Aluno não encontrado',
            ], 404);
        }

        return response()->json([
            'message' => 'Aluno excluído com sucesso',
        ]);
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

