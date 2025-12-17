<?php

namespace App\Http\Controllers\modules\Agenda;

use App\Services\AgendaService;
use App\Services\Provider\ProviderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ProviderController
{
    public function __construct(
        private AgendaService $agendaService,
        private ProviderService $providerService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $tenantId = null;

        if (!$user->is_super_admin) {
            $tenantId = $user->tenant_id ?? $user->tenantUsers()->first()?->tenant_id;
            
            if (!$tenantId) {
                return response()->json([
                    'message' => 'Tenant não identificado',
                ], 400);
            }
        }

        $filters = $request->only([]);
        $providers = $this->agendaService->getAllProviders($tenantId, $filters);

        return response()->json($providers);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $tenantId = null;

        if (!$user->is_super_admin) {
            $tenantId = $user->tenant_id ?? $user->tenantUsers()->first()?->tenant_id;
            
            if (!$tenantId) {
                return response()->json([
                    'message' => 'Tenant não identificado',
                ], 400);
            }
        }

        $provider = $this->agendaService->getProviderById($id, $tenantId);

        if (!$provider) {
            return response()->json([
                'message' => 'Profissional não encontrado',
            ], 404);
        }

        return response()->json($provider);
    }

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
            'cpf' => [
                'required',
                'string',
                'size:14',
                Rule::unique('persons', 'cpf')->where(function ($query) use ($tenantId) {
                    return $query->where('tenant_id', $tenantId);
                }),
            ],
            'rg' => 'nullable|string|max:20',
            'birth_date' => 'required|date',
            'phone' => 'nullable|string|max:20',
            'address_street' => 'nullable|string|max:255',
            'address_number' => 'nullable|string|max:20',
            'address_neighborhood' => 'nullable|string|max:255',
            'address_city' => 'nullable|string|max:255',
            'address_state' => 'nullable|string|size:2',
            'address_zip' => 'nullable|string|max:10',
            'service_ids' => 'nullable|array',
            'service_ids.*' => 'integer|exists:services,id',
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
        } elseif ($request->hasFile('photo_url')) {
            $data['photo'] = $request->file('photo_url');
        }
        
        $provider = $this->providerService->create($tenantId, $data);

        return response()->json($provider, 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $tenantId = $request->user()->tenant_id ?? $request->user()->tenantUsers()->first()?->tenant_id;
        
        if (!$tenantId) {
            return response()->json([
                'message' => 'Tenant não identificado',
            ], 400);
        }

        $provider = \App\Models\Provider::with('person')->find($id);
        if (!$provider) {
            return response()->json(['message' => 'Profissional não encontrado'], 404);
        }

        // O middleware HandlePutFormData deve ter processado os dados
        $requestData = $request->all();
        
        // Processa service_ids se vier como array indexado (service_ids[0], service_ids[1], etc)
        // Também verifica se vem como string separada por vírgula ou array simples
        if ($request->has('service_ids')) {
            $serviceIds = $request->input('service_ids');
            
            // Se for string, tenta converter para array
            if (is_string($serviceIds)) {
                $serviceIds = explode(',', $serviceIds);
                $serviceIds = array_map('trim', $serviceIds);
            }
            
            // Se for array, normaliza
            if (is_array($serviceIds)) {
                $serviceIds = array_values($serviceIds); // Remove índices e reordena
                $serviceIds = array_filter($serviceIds, function($value) {
                    return $value !== null && $value !== '';
                }); // Remove valores vazios
                $serviceIds = array_map('intval', $serviceIds); // Converte para inteiros
                $serviceIds = array_values(array_unique($serviceIds)); // Remove duplicatas e reindexa
            } else {
                $serviceIds = [];
            }
            
            $requestData['service_ids'] = $serviceIds;
        }

        // Valida arquivo manualmente se existir
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
            unset($requestData['photo']);
        }
        
        // Busca a person_id do provider para a validação de CPF
        $personId = $provider->person_id ?? null;
        
        // Busca o User atual através do email (se fornecido) ou deixa null
        $currentUserId = null;
        if (isset($requestData['email'])) {
            $currentUser = \App\Models\User::where('email', $requestData['email'])
                ->where('tenant_id', $tenantId)
                ->first();
            $currentUserId = $currentUser?->id;
        }
        
        $rules = [
            'name' => 'sometimes|string|max:255',
            'email' => [
                'sometimes',
                'email',
                Rule::unique('users', 'email')->ignore($currentUserId),
            ],
            'cpf' => [
                'sometimes',
                'string',
                'size:14',
                Rule::unique('persons', 'cpf')
                    ->where(function ($query) use ($tenantId) {
                        return $query->where('tenant_id', $tenantId);
                    })
                    ->ignore($personId),
            ],
            'rg' => 'nullable|string|max:20',
            'birth_date' => 'sometimes|date',
            'phone' => 'nullable|string|max:20',
            'address_street' => 'nullable|string|max:255',
            'address_number' => 'nullable|string|max:20',
            'address_neighborhood' => 'nullable|string|max:255',
            'address_city' => 'nullable|string|max:255',
            'address_state' => 'nullable|string|size:2',
            'address_zip' => 'nullable|string|max:10',
            'service_ids' => 'nullable|array',
            'service_ids.*' => 'integer|exists:services,id',
        ];

        $validator = Validator::make($requestData, $rules);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Erro na validação',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Prepara os dados validados incluindo o arquivo de foto se existir
        $data = $validator->validated();
        
        // Adiciona o arquivo de foto se foi validado manualmente
        if ($photoFile) {
            $data['photo'] = $photoFile;
        } elseif ($request->input('photo') === null) {
            // Se enviar null explicitamente, remove a foto
            $data['photo'] = null;
        }
        
        $provider = $this->providerService->update($id, $tenantId, $data);

        if (!$provider) {
            return response()->json([
                'message' => 'Profissional não encontrado',
            ], 404);
        }

        return response()->json($provider);
    }

    public function destroy(Request $request, int|string|null $id = null): JsonResponse
    {
        $user = $request->user();
        $tenantId = null;

        if (!$user->is_super_admin) {
            $tenantId = $user->tenant_id ?? $user->tenantUsers()->first()?->tenant_id;
            
            if (!$tenantId) {
                return response()->json([
                    'message' => 'Tenant não identificado',
                ], 400);
            }
        } else {
            return response()->json([
                'message' => 'Super admin não pode excluir profissionais',
            ], 403);
        }

        $ids = null;
        if ($id !== null && $id !== 'batch') {
            $ids = is_numeric($id) ? (int) $id : null;
            if ($ids === null) {
                return response()->json([
                    'message' => 'ID inválido na URL',
                ], 400);
            }
        } elseif ($request->has('ids') && is_array($request->ids)) {
            $ids = $request->ids;
        } else {
            return response()->json([
                'message' => 'ID ou array de IDs não fornecido',
            ], 400);
        }

        $idsArray = is_array($ids) ? $ids : [$ids];
        $deleted = [];
        $notFound = [];

        foreach ($idsArray as $providerId) {
            $result = $this->agendaService->deleteProvider($providerId, $tenantId);
            if ($result) {
                $deleted[] = $providerId;
            } else {
                $notFound[] = $providerId;
            }
        }

        if (empty($deleted)) {
            return response()->json([
                'message' => 'Nenhum profissional encontrado',
                'not_found' => $notFound,
            ], 404);
        }

        $response = [
            'message' => count($deleted) > 1 
                ? count($deleted) . ' profissionais excluídos com sucesso'
                : 'Profissional excluído com sucesso',
            'deleted' => $deleted,
        ];

        if (!empty($notFound)) {
            $response['not_found'] = $notFound;
        }

        return response()->json($response);
    }
}

