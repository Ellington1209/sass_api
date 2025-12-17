<?php

namespace Database\Seeders;

use App\Models\Module;
use App\Models\Service;
use App\Models\Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::first();
        
        if (!$tenant) {
            $this->command->warn('Nenhum tenant encontrado. Criando tenant de exemplo...');
            $tenant = Tenant::create([
                'name' => 'Tenant Exemplo',
                'active' => true,
            ]);
        }

        $autoEscolaModuleId = 10;
        $barbeariaModuleId = 11;
        $salaoBelezaModuleId = 12;

        $autoEscolaModule = Module::find($autoEscolaModuleId);
        $barbeariaModule = Module::find($barbeariaModuleId);
        $salaoBelezaModule = Module::find($salaoBelezaModuleId);

        if (!$autoEscolaModule) {
            $this->command->warn("Módulo com ID {$autoEscolaModuleId} não encontrado. Pulando serviços de auto-escola.");
            $autoEscolaModuleId = null;
        }

        if (!$barbeariaModule) {
            $this->command->warn("Módulo com ID {$barbeariaModuleId} não encontrado. Pulando serviços de barbearia.");
            $barbeariaModuleId = null;
        }

        if (!$salaoBelezaModule) {
            $this->command->warn("Módulo com ID {$salaoBelezaModuleId} não encontrado. Pulando serviços de salão de beleza.");
            $salaoBelezaModuleId = null;
        }

        $services = [];

        if ($autoEscolaModuleId) {
            $services[] = [
                'tenant_id' => $tenant->id,
                'name' => 'Aulas Práticas',
                'slug' => 'aulas-praticas',
                'duration_minutes' => 50,
                'module_id' => $autoEscolaModuleId,
                'active' => true,
            ];

            $services[] = [
                'tenant_id' => $tenant->id,
                'name' => 'Aulas Teóricas',
                'slug' => 'aulas-teoricas',
                'duration_minutes' => 50,
                'module_id' => $autoEscolaModuleId,
                'active' => true,
            ];
        }

        if ($barbeariaModuleId) {
            $services[] = [
                'tenant_id' => $tenant->id,
                'name' => 'Corte Masculino',
                'slug' => 'corte-masculino',
                'duration_minutes' => 30,
                'module_id' => $barbeariaModuleId,
                'active' => true,
            ];

            $services[] = [
                'tenant_id' => $tenant->id,
                'name' => 'Barba',
                'slug' => 'barba',
                'duration_minutes' => 20,
                'module_id' => $barbeariaModuleId,
                'active' => true,
            ];

            $services[] = [
                'tenant_id' => $tenant->id,
                'name' => 'Corte + Barba',
                'slug' => 'corte-barba',
                'duration_minutes' => 45,
                'module_id' => $barbeariaModuleId,
                'active' => true,
            ];

            $services[] = [
                'tenant_id' => $tenant->id,
                'name' => 'Sobrancelha',
                'slug' => 'sobrancelha',
                'duration_minutes' => 15,
                'module_id' => $barbeariaModuleId,
                'active' => true,
            ];

            $services[] = [
                'tenant_id' => $tenant->id,
                'name' => 'Tratamento Capilar',
                'slug' => 'tratamento-capilar',
                'duration_minutes' => 40,
                'module_id' => $barbeariaModuleId,
                'active' => true,
            ];
        }

        if ($salaoBelezaModuleId) {
            $services[] = [
                'tenant_id' => $tenant->id,
                'name' => 'Corte Feminino',
                'slug' => 'corte-feminino',
                'duration_minutes' => 45,
                'module_id' => $salaoBelezaModuleId,
                'active' => true,
            ];

            $services[] = [
                'tenant_id' => $tenant->id,
                'name' => 'Escova',
                'slug' => 'escova',
                'duration_minutes' => 60,
                'module_id' => $salaoBelezaModuleId,
                'active' => true,
            ];

            $services[] = [
                'tenant_id' => $tenant->id,
                'name' => 'Coloração',
                'slug' => 'coloracao',
                'duration_minutes' => 120,
                'module_id' => $salaoBelezaModuleId,
                'active' => true,
            ];

            $services[] = [
                'tenant_id' => $tenant->id,
                'name' => 'Manicure',
                'slug' => 'manicure',
                'duration_minutes' => 45,
                'module_id' => $salaoBelezaModuleId,
                'active' => true,
            ];

            $services[] = [
                'tenant_id' => $tenant->id,
                'name' => 'Pedicure',
                'slug' => 'pedicure',
                'duration_minutes' => 50,
                'module_id' => $salaoBelezaModuleId,
                'active' => true,
            ];

            $services[] = [
                'tenant_id' => $tenant->id,
                'name' => 'Manicure + Pedicure',
                'slug' => 'manicure-pedicure',
                'duration_minutes' => 90,
                'module_id' => $salaoBelezaModuleId,
                'active' => true,
            ];

            $services[] = [
                'tenant_id' => $tenant->id,
                'name' => 'Maquiagem',
                'slug' => 'maquiagem',
                'duration_minutes' => 60,
                'module_id' => $salaoBelezaModuleId,
                'active' => true,
            ];

            $services[] = [
                'tenant_id' => $tenant->id,
                'name' => 'Design de Sobrancelhas',
                'slug' => 'design-sobrancelhas',
                'duration_minutes' => 30,
                'module_id' => $salaoBelezaModuleId,
                'active' => true,
            ];
        }

        foreach ($services as $service) {
            Service::updateOrCreate(
                [
                    'tenant_id' => $service['tenant_id'],
                    'slug' => $service['slug'],
                    'module_id' => $service['module_id'],
                ],
                $service
            );
        }

        $this->command->info('Serviços criados com sucesso!');
        $this->command->info('Total: ' . count($services) . ' serviços');
    }
}

