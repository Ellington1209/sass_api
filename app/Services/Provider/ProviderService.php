<?php

namespace App\Services\Provider;

use App\Models\File;
use App\Models\Person;
use App\Models\Provider;
use App\Models\User;
use App\Models\UserPermission;
use App\Services\FileService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class ProviderService
{
    public function __construct(
        private FileService $fileService
    ) {}

    /**
     * Cria um novo provider
     */
    public function create(int $tenantId, array $data): array
    {
        // Valida se name e email foram fornecidos
        if (!isset($data['name']) || !isset($data['email'])) {
            throw new \Exception('Nome e email são obrigatórios para criar o profissional.');
        }

        return DB::transaction(function () use ($tenantId, $data) {
            // Extrai os primeiros 6 dígitos do CPF (remove pontos e traços)
            $cpfClean = preg_replace('/[^0-9]/', '', $data['cpf'] ?? '');
            $password = substr($cpfClean, 0, 6);

            // 1. Cria o usuário primeiro
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($password),
                'tenant_id' => $tenantId,
                'is_super_admin' => false,
            ]);

            // Associa as permissões padrão para provider
            $permissions = [
                'agenda.services.view',
                'agenda.services.create',
                'files.view',
                'files.upload',
                'files.download',
                'students.view',
                'students.create',
            ];

            foreach ($permissions as $permission) {
                UserPermission::create([
                    'user_id' => $user->id,
                    'permission_key' => $permission,
                ]);
            }

            // Faz upload da foto se fornecida
            $photoUrl = null;
            if (isset($data['photo']) && $data['photo'] instanceof UploadedFile) {
                try {
                    $photoUrl = $this->uploadPhoto($data['photo'], $tenantId, $user->id);
                } catch (\Exception $e) {
                    throw $e;
                }
            } elseif (isset($data['photo_url']) && is_string($data['photo_url'])) {
                $photoUrl = $data['photo_url'];
            }

            // 2. Cria a pessoa com os dados pessoais (incluindo photo_url)
            $person = Person::create([
                'tenant_id' => $tenantId,
                'user_id' => $user->id,
                'cpf' => $data['cpf'] ?? null,
                'rg' => $data['rg'] ?? null,
                'birth_date' => $data['birth_date'] ?? null,
                'phone' => $data['phone'] ?? null,
                'address_street' => $data['address_street'] ?? null,
                'address_number' => $data['address_number'] ?? null,
                'address_complement' => $data['address_complement'] ?? null,
                'address_neighborhood' => $data['address_neighborhood'] ?? null,
                'address_city' => $data['address_city'] ?? null,
                'address_state' => $data['address_state'] ?? null,
                'address_zip' => $data['address_zip'] ?? null,
                'photo_url' => $photoUrl,
            ]);

            // 3. Cria o provider referenciando a pessoa
            $providerData = [
                'tenant_id' => $tenantId,
                'person_id' => $person->id,
                'service_ids' => $data['service_ids'] ?? null,
            ];

            $provider = Provider::create($providerData);
            $provider->load(['person.user']);

            return $this->formatProvider($provider);
        });
    }

    /**
     * Atualiza um provider
     */
    public function update(int $id, int $tenantId, array $data): ?array
    {
        return DB::transaction(function () use ($id, $tenantId, $data) {
            $provider = Provider::where('id', $id)
                ->where('tenant_id', $tenantId)
                ->with('person.user')
                ->first();

            if (!$provider) {
                return null;
            }

            // Atualiza dados do usuário se fornecidos (name e email)
            if (isset($data['name']) || isset($data['email'])) {
                $user = $provider->person->user;
                
                if ($user) {
                    $userData = [];
                    if (isset($data['name'])) {
                        $userData['name'] = $data['name'];
                    }
                    if (isset($data['email'])) {
                        $userData['email'] = $data['email'];
                    }
                    $user->update($userData);
                }
                
                unset($data['name'], $data['email']);
            }

            // Atualiza dados da pessoa se fornecidos
            $personData = [];
            $personFields = [
                'cpf', 'rg', 'birth_date', 'phone',
                'address_street', 'address_number', 'address_complement',
                'address_neighborhood', 'address_city', 'address_state', 'address_zip'
            ];

            foreach ($personFields as $field) {
                if (isset($data[$field])) {
                    $personData[$field] = $data[$field];
                    unset($data[$field]);
                }
            }

            // Faz upload da foto se fornecida
            if (isset($data['photo']) && $data['photo'] instanceof UploadedFile) {
                // Remove foto antiga se existir
                if ($provider->person->photo_url) {
                    $this->deletePhoto($provider->person->photo_url);
                }
                // Usa o ID do usuário para o upload
                $personData['photo_url'] = $this->uploadPhoto($data['photo'], $tenantId, $provider->person->user_id);
                unset($data['photo']);
            } elseif (isset($data['photo']) && $data['photo'] === null) {
                // Se enviar null, remove a foto
                if ($provider->person->photo_url) {
                    $this->deletePhoto($provider->person->photo_url);
                }
                $personData['photo_url'] = null;
                unset($data['photo']);
            } elseif (isset($data['photo_url']) && is_string($data['photo_url'])) {
                $personData['photo_url'] = $data['photo_url'];
                unset($data['photo_url']);
            }

            if (!empty($personData)) {
                $provider->person->update($personData);
            }

            // Remove person_id se estiver presente, pois não pode ser alterado
            unset($data['person_id']);
            
            // Atualiza dados do provider (service_ids)
            if (!empty($data)) {
                $provider->update($data);
            }
            
            $provider->refresh();
            $provider->load(['person.user']);

            return $this->formatProvider($provider);
        });
    }

    /**
     * Formata os dados do provider para resposta
     */
    private function formatProvider(Provider $provider): array
    {
        $person = $provider->person;
        $user = $person?->user;

        return [
            'id' => $provider->id,
            'tenant_id' => $provider->tenant_id,
            'person_id' => $provider->person_id,
            'user' => $user ? [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ] : null,
            'person' => $person ? [
                'id' => $person->id,
                'cpf' => $person->cpf,
                'rg' => $person->rg,
                'birth_date' => $person->birth_date?->format('Y-m-d'),
                'phone' => $person->phone,
                'address' => [
                    'street' => $person->address_street,
                    'number' => $person->address_number,
                    'complement' => $person->address_complement,
                    'neighborhood' => $person->address_neighborhood,
                    'city' => $person->address_city,
                    'state' => $person->address_state,
                    'zip' => $person->address_zip,
                ],
            ] : null,
            'photo_url' => $person?->photo_url ? (
                str_starts_with($person->photo_url, 'tenants/') 
                    ? url('/api/files/public/' . urlencode($person->photo_url))
                    : $person->photo_url
            ) : null,
            'service_ids' => $provider->service_ids,
            'created_at' => $provider->created_at?->toISOString(),
            'updated_at' => $provider->updated_at?->toISOString(),
        ];
    }

    /**
     * Faz upload da foto do provider no B2
     * 
     * @param UploadedFile $file
     * @param int $tenantId
     * @param int $userId
     * @return string URL temporária da foto salva
     */
    private function uploadPhoto(UploadedFile $file, int $tenantId, int $userId): string
    {
        $allowedMimes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($file->getMimeType(), $allowedMimes)) {
            throw new \Exception('Tipo de arquivo não permitido. Use apenas imagens (JPEG, PNG, GIF, WEBP).');
        }

        if ($file->getSize() > 5 * 1024 * 1024) {
            throw new \Exception('Arquivo muito grande. Tamanho máximo: 5MB.');
        }

        $fileRecord = $this->fileService->upload($file, $tenantId, 'avatar', $userId);
        
        return $fileRecord->path;
    }

    /**
     * Remove a foto do provider do B2
     * 
     * @param string $photoPath
     * @return void
     */
    private function deletePhoto(string $photoPath): void
    {
        $file = File::where('path', $photoPath)->first();

        if ($file) {
            $this->fileService->delete($file->path);
            $file->delete();
        }
    }
}

