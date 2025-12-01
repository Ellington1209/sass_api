<?php

namespace App\Services\Student;

use App\Models\StatusStudent;
use Illuminate\Support\Collection;

class StatusStudentService
{
    /**
     * Obtém todos os status de alunos ativos
     */
    public function getAll(): Collection
    {
        return StatusStudent::where('active', true)
            ->orderBy('order')
            ->get()
            ->map(function ($status) {
                return $this->formatStatus($status);
            });
    }

    /**
     * Obtém um status por ID
     */
    public function getById(int $id): ?array
    {
        $status = StatusStudent::find($id);

        if (!$status) {
            return null;
        }

        return $this->formatStatus($status);
    }

    /**
     * Formata os dados do status para resposta
     */
    private function formatStatus(StatusStudent $status): array
    {
        return [
            'id' => $status->id,
            'key' => $status->key,
            'name' => $status->name,
            'description' => $status->description,
            'order' => $status->order,
            'active' => $status->active,
            'created_at' => $status->created_at?->toISOString(),
            'updated_at' => $status->updated_at?->toISOString(),
        ];
    }
}

