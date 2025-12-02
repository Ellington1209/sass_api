<?php

namespace App\Http\Controllers\modules\Admin\Module;

use App\Services\Admin\Module\ModuleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ModuleController
{
    public function __construct(
        private ModuleService $moduleService
    ) {}

    /**
     * Lista todos os módulos ativos
     */
    public function index(Request $request): JsonResponse
    {
        $modules = $this->moduleService->getActiveModules();

        return response()->json($modules->toArray());
    }
}

