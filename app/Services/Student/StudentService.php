<?php

namespace App\Services\Student;

use App\Models\Student;
use App\Models\StudentDocument;
use App\Models\StudentNote;
use App\Models\User;
use App\Models\UserPermission;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class StudentService
{
    /**
     * Obtém todos os alunos do tenant com paginação
     */
    public function getAll(int $tenantId, ?array $filters = null, int $perPage = 15, int $page = 1): array
    {
        $query = Student::where('tenant_id', $tenantId)
            ->with(['user', 'statusStudent']);

        // Filtros opcionais
        if ($filters) {
            if (isset($filters['status'])) {
                // Pode ser status_students_id ou key do status
                if (is_numeric($filters['status'])) {
                    $query->where('status_students_id', $filters['status']);
                } else {
                    $query->whereHas('statusStudent', function ($q) use ($filters) {
                        $q->where('key', $filters['status']);
                    });
                }
            }
            if (isset($filters['category'])) {
                $query->where('category', $filters['category']);
            }
            if (isset($filters['search'])) {
                $search = $filters['search'];
                $query->where(function ($q) use ($search) {
                    $q->where('cpf', 'ilike', "%{$search}%")
                        ->orWhereHas('user', function ($userQuery) use ($search) {
                            $userQuery->where('name', 'ilike', "%{$search}%")
                                ->orWhere('email', 'ilike', "%{$search}%");
                        });
                });
            }
        }

        // Ordenação: por nome do usuário (usando subquery para evitar problemas com join)
        $query->orderByRaw('(SELECT name FROM users WHERE users.id = students.user_id)');

        // Paginação
        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        // Carrega os relacionamentos necessários
        $paginator->getCollection()->load(['user', 'statusStudent', 'documents', 'notes.user']);

        // Formata os resultados
        $formattedStudents = $paginator->getCollection()->map(function ($student) {
            return $this->formatStudent($student);
        });

        return [
            'data' => $formattedStudents->toArray(),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
        ];
    }

    /**
     * Obtém um aluno por ID
     */
    public function getById(int $id, int $tenantId): ?array
    {
        $student = Student::where('id', $id)
            ->where('tenant_id', $tenantId)
            ->with(['user', 'statusStudent', 'documents', 'notes.user'])
            ->first();

        if (!$student) {
            return null;
        }

        return $this->formatStudent($student);
    }

    /**
     * Cria um novo aluno
     */
    public function create(int $tenantId, array $data): array
    {
        // Valida se name e email foram fornecidos
        if (!isset($data['name']) || !isset($data['email'])) {
            throw new \Exception('Nome e email são obrigatórios para criar o aluno.');
        }

        // Extrai os primeiros 6 dígitos do CPF (remove pontos e traços)
        $cpfClean = preg_replace('/[^0-9]/', '', $data['cpf']);
        $password = substr($cpfClean, 0, 6);

        // Cria o usuário primeiro
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($password),
            'tenant_id' => $tenantId,
            'is_super_admin' => false,
        ]);

        // Associa a permissão padrão de upload de documentos
        UserPermission::create([
            'user_id' => $user->id,
            'permission_key' => 'students.upload_document',
        ]);

        // Faz upload da foto se fornecida
        $photoUrl = null;
        if (isset($data['photo']) && $data['photo'] instanceof UploadedFile) {
            $photoUrl = $this->uploadPhoto($data['photo'], $tenantId, $user->id);
        } elseif (isset($data['photo_url'])) {
            $photoUrl = $data['photo_url'];
        }

        // Prepara os dados do aluno
        $studentData = [
            'tenant_id' => $tenantId,
            'user_id' => $user->id,
            'cpf' => $data['cpf'],
            'rg' => $data['rg'] ?? null,
            'birth_date' => $data['birth_date'],
            'phone' => $data['phone'] ?? null,
            'address_street' => $data['address_street'] ?? null,
            'address_number' => $data['address_number'] ?? null,
            'address_neighborhood' => $data['address_neighborhood'] ?? null,
            'address_city' => $data['address_city'] ?? null,
            'address_state' => $data['address_state'] ?? null,
            'address_zip' => $data['address_zip'] ?? null,
            'category' => $data['category'] ?? null,
            'photo_url' => $photoUrl,
        ];

        // Se não foi informado status_students_id, busca o status padrão (pre-cadastro)
        if (!isset($data['status_students_id']) || !$data['status_students_id']) {
            $defaultStatus = \App\Models\StatusStudent::where('key', 'pre-cadastro')->first();
            if ($defaultStatus) {
                $studentData['status_students_id'] = $defaultStatus->id;
            }
        } else {
            $studentData['status_students_id'] = $data['status_students_id'];
        }
        
        $student = Student::create($studentData);
        $student->load(['user', 'statusStudent', 'documents', 'notes.user']);

        return $this->formatStudent($student);
    }

    /**
     * Atualiza um aluno
     */
    public function update(int $id, int $tenantId, array $data): ?array
    {
        $student = Student::where('id', $id)
            ->where('tenant_id', $tenantId)
            ->first();

        if (!$student) {
            return null;
        }

        // Remove user_id se estiver presente, pois não pode ser alterado
        unset($data['user_id']);
        
        // Faz upload da foto se fornecida
        if (isset($data['photo']) && $data['photo'] instanceof UploadedFile) {
            // Remove foto antiga se existir
            if ($student->photo_url) {
                $this->deletePhoto($student->photo_url);
            }
            $data['photo_url'] = $this->uploadPhoto($data['photo'], $tenantId, $student->user_id);
            unset($data['photo']); // Remove o arquivo do array antes de atualizar
        } elseif (isset($data['photo_url'])) {
            // Se for uma URL, mantém
        } elseif (isset($data['photo']) && $data['photo'] === null) {
            // Se enviar null, remove a foto
            if ($student->photo_url) {
                $this->deletePhoto($student->photo_url);
            }
            $data['photo_url'] = null;
            unset($data['photo']);
        }
        
        $student->update($data);
        $student->load(['user', 'statusStudent', 'documents', 'notes.user']);

        return $this->formatStudent($student);
    }

    /**
     * Exclui um aluno
     */
    public function delete(int $id, int $tenantId): bool
    {
        $student = Student::where('id', $id)
            ->where('tenant_id', $tenantId)
            ->first();

        if (!$student) {
            return false;
        }

        return $student->delete();
    }

    /**
     * Adiciona um documento ao aluno
     */
    public function addDocument(int $studentId, int $tenantId, array $data): array
    {
        $student = Student::where('id', $studentId)
            ->where('tenant_id', $tenantId)
            ->first();

        if (!$student) {
            throw new \Exception('Aluno não encontrado');
        }

        $data['student_id'] = $studentId;
        $data['tenant_id'] = $tenantId;
        $document = StudentDocument::create($data);

        return [
            'id' => $document->id,
            'student_id' => $document->student_id,
            'type' => $document->type,
            'file_url' => $document->file_url,
            'file_name' => $document->file_name,
            'description' => $document->description,
            'created_at' => $document->created_at?->toISOString(),
        ];
    }

    /**
     * Remove um documento do aluno
     */
    public function removeDocument(int $documentId, int $tenantId): bool
    {
        $document = StudentDocument::where('id', $documentId)
            ->where('tenant_id', $tenantId)
            ->first();

        if (!$document) {
            return false;
        }

        return $document->delete();
    }

    /**
     * Adiciona uma observação ao aluno
     */
    public function addNote(int $studentId, int $tenantId, int $userId, string $note): array
    {
        $student = Student::where('id', $studentId)
            ->where('tenant_id', $tenantId)
            ->first();

        if (!$student) {
            throw new \Exception('Aluno não encontrado');
        }

        $studentNote = StudentNote::create([
            'student_id' => $studentId,
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'note' => $note,
        ]);

        $studentNote->load('user');

        return [
            'id' => $studentNote->id,
            'student_id' => $studentNote->student_id,
            'user' => [
                'id' => $studentNote->user->id,
                'name' => $studentNote->user->name,
                'email' => $studentNote->user->email,
            ],
            'note' => $studentNote->note,
            'created_at' => $studentNote->created_at?->toISOString(),
        ];
    }

    /**
     * Remove uma observação do aluno
     */
    public function removeNote(int $noteId, int $tenantId): bool
    {
        $note = StudentNote::where('id', $noteId)
            ->where('tenant_id', $tenantId)
            ->first();

        if (!$note) {
            return false;
        }

        return $note->delete();
    }

    /**
     * Formata os dados do aluno para resposta
     */
    private function formatStudent(Student $student): array
    {
        return [
            'id' => $student->id,
            'tenant_id' => $student->tenant_id,
            'user_id' => $student->user_id,
            'user' => $student->user ? [
                'id' => $student->user->id,
                'name' => $student->user->name,
                'email' => $student->user->email,
            ] : null,
            'cpf' => $student->cpf,
            'rg' => $student->rg,
            'birth_date' => $student->birth_date?->format('Y-m-d'),
            'phone' => $student->phone,
            'address' => [
                'street' => $student->address_street,
                'number' => $student->address_number,
                'neighborhood' => $student->address_neighborhood,
                'city' => $student->address_city,
                'state' => $student->address_state,
                'zip' => $student->address_zip,
            ],
            'category' => $student->category,
            'status' => $student->statusStudent ? [
                'id' => $student->statusStudent->id,
                'key' => $student->statusStudent->key,
                'name' => $student->statusStudent->name,
                'description' => $student->statusStudent->description,
            ] : null,
            'photo_url' => $student->photo_url,
            'documents' => $student->documents->map(function ($doc) {
                return [
                    'id' => $doc->id,
                    'type' => $doc->type,
                    'file_url' => $doc->file_url,
                    'file_name' => $doc->file_name,
                    'description' => $doc->description,
                    'created_at' => $doc->created_at?->toISOString(),
                ];
            }),
            'notes' => $student->notes->map(function ($note) {
                return [
                    'id' => $note->id,
                    'user' => [
                        'id' => $note->user->id,
                        'name' => $note->user->name,
                        'email' => $note->user->email,
                    ],
                    'note' => $note->note,
                    'created_at' => $note->created_at?->toISOString(),
                ];
            }),
            'created_at' => $student->created_at?->toISOString(),
            'updated_at' => $student->updated_at?->toISOString(),
        ];
    }

    /**
     * Faz upload da foto do aluno
     * Por enquanto salva no storage local, mas pode ser facilmente migrado para S3
     * 
     * @param UploadedFile $file
     * @param int $tenantId
     * @param int $userId
     * @return string URL da foto salva
     */
    private function uploadPhoto(UploadedFile $file, int $tenantId, int $userId): string
    {
        // Valida o arquivo
        $allowedMimes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($file->getMimeType(), $allowedMimes)) {
            throw new \Exception('Tipo de arquivo não permitido. Use apenas imagens (JPEG, PNG, GIF, WEBP).');
        }

        // Tamanho máximo: 5MB
        if ($file->getSize() > 5 * 1024 * 1024) {
            throw new \Exception('Arquivo muito grande. Tamanho máximo: 5MB.');
        }

        // Gera nome único para o arquivo
        $fileName = 'student_' . $userId . '_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
        
        // Caminho: storage/app/public/students/{tenant_id}/{filename}
        $path = $file->storeAs('students/' . $tenantId, $fileName, 'public');

        // Retorna a URL pública
        return Storage::url($path);
    }

    /**
     * Remove a foto do aluno
     * 
     * @param string $photoUrl
     * @return void
     */
    private function deletePhoto(string $photoUrl): void
    {
        // Remove /storage/ da URL para obter o caminho relativo
        $path = str_replace('/storage/', '', parse_url($photoUrl, PHP_URL_PATH));
        
        // Se o arquivo existe no storage público, remove
        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}

