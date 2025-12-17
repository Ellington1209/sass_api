<?php

namespace Database\Seeders;

use App\Models\Module;
use Illuminate\Database\Seeder;

class ModuleSeeder extends Seeder
{
    public function run(): void
    {
        $modules = [
            [
                'key' => 'admin',
                'name' => 'Admin',
                'description' => 'Administração do sistema',
                'active' => true,
            ],
            [
                'key' => 'cliente',
                'name' => 'Módulo Cliente',
                'description' => 'Gerenciamento de clientes e seus dados',
                'active' => true,
            ],
            [
                'key' => 'estoque',
                'name' => 'Estoque',
                'description' => 'Gerenciamento de estoque e produtos',
                'active' => true,
            ],
            [
                'key' => 'financeiro',
                'name' => 'Financeiro',
                'description' => 'Gerenciamento financeiro e transações',
                'active' => true,
            ],
            [
                'key' => 'usuario',
                'name' => 'Usuário',
                'description' => 'Gerenciamento de usuários do sistema',
                'active' => true,
            ],
            [
                'key' => 'students',
                'name' => 'Alunos',
                'description' => 'Gerenciamento de alunos e seus dados',
                'active' => true,
            ],
            [
                'key' => 'files',
                'name' => 'Arquivos',
                'description' => 'Gerenciamento de arquivos e uploads',
                'active' => true,
            ],
            [
                'key' => 'settings',
                'name' => 'Configurações',
                'description' => 'Gerenciamento de configurações do sistema',
                'active' => true,
            ],
            [
                'key' => 'agenda',
                'name' => 'Agenda',
                'description' => 'Gerenciamento de serviços, profissionais e agendamentos',
                'active' => true,
            ],
            [
                'key' => 'auto-escola',
                'name' => 'Auto Escola',
                'description' => 'Gerenciamento de auto escola',
                'active' => true,
            ],
            [
                'key' => 'barbearia',
                'name' => 'Barbearia',
                'description' => 'Gerenciamento de barbearia',
                'active' => true,
            ],
            [
                'key' => 'salão-de-beleza',
                'name' => 'Salão de Beleza',
                'description' => 'Gerenciamento de barbearia',
                'active' => true,
            ],
            [
                'key' => 'service',
                'name' => 'Serviço',
                'description' => 'Gerenciamento de serviço',
                'active' => true,
            ],
            [
                'key' => 'provider',
                'name' => 'Profissional',
                'description' => 'Gerenciamento de profissional',
                'active' => true,
            ],
        ];

        foreach ($modules as $module) {
            Module::updateOrCreate(
                ['key' => $module['key']],
                $module
            );
        }

        $this->command->info('Modules created successfully!');
    }
}

