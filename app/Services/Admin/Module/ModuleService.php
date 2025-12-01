<?php

namespace App\Services\Admin\Module;

use App\Models\Module;
use Illuminate\Support\Collection;

class ModuleService
{
    /**
     * Obtém todos os módulos ativos
     */
    public function getActiveModules(): Collection
    {
        return Module::where('active', true)
            ->orderBy('name')
            ->get()
            ->map(function ($module) {
                return $this->formatModule($module);
            });
    }

    /**
     * Formata os dados do módulo para resposta
     */
    private function formatModule(Module $module): array
    {
        return [
            'id' => $module->id,
            'key' => $module->key,
            'name' => $module->name,
            'description' => $module->description,
            'active' => $module->active,
            'created_at' => $module->created_at?->toISOString(),
            'updated_at' => $module->updated_at?->toISOString(),
        ];
    }
}

