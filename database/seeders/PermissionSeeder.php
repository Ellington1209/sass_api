<?php

namespace Database\Seeders;

use App\Models\Module;
use App\Models\Permission;
use App\Models\User;
use App\Models\UserPermission;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Buscar todos os módulos e criar mapeamento key => id
        $modules = Module::all()->keyBy('key');
        $moduleIds = $modules->mapWithKeys(function ($module) {
            return [$module->key => $module->id];
        })->toArray();

        // Verificar se os módulos necessários existem
        $requiredModules = ['admin', 'cliente', 'estoque', 'financeiro', 'usuario', 'students', 'files'];
        $missingModules = array_diff($requiredModules, array_keys($moduleIds));
        
        if (!empty($missingModules)) {
            $this->command->error('Módulos não encontrados: ' . implode(', ', $missingModules));
            $this->command->warn('Execute o ModuleSeeder primeiro para criar os módulos necessários.');
            return;
        }

        $permissions = $this->getPermissions($moduleIds);

        foreach ($permissions as $permission) {
            Permission::updateOrCreate(
                ['permission_key' => $permission['key']],
                [
                    'module_id' => $permission['module_id'],
                    'descricao' => $permission['description'],
                ]
            );
        }

        $this->command->info('Permissions created successfully!');

        // Associar todas as permissões ao super admin
        $superAdmin = User::where('is_super_admin', true)->first();

        if ($superAdmin) {
            foreach ($permissions as $permission) {
                UserPermission::updateOrCreate(
                    [
                        'user_id' => $superAdmin->id,
                        'permission_key' => $permission['key'],
                    ]
                );
            }

            $this->command->info("All permissions assigned to super admin: {$superAdmin->email}");
        } else {
            $this->command->warn('Super admin user not found. Run DatabaseSeeder first.');
        }
    }

    private function getPermissions(array $moduleIds): array
    {
        return [
            // ========== ADMINISTRAÇÃO - TENANTS ==========
            [
                'key' => 'admin.tenants.view',
                'module_id' => $moduleIds['admin'],
                'description' => 'Visualizar lista de tenants',
            ],
            [
                'key' => 'admin.tenants.create',
                'module_id' => $moduleIds['admin'],
                'description' => 'Criar novos tenants',
            ],
            [
                'key' => 'admin.tenants.edit',
                'module_id' => $moduleIds['admin'],
                'description' => 'Editar tenants existentes',
            ],
            [
                'key' => 'admin.tenants.delete',
                'module_id' => $moduleIds['admin'],
                'description' => 'Excluir tenants',
            ],
            [
                'key' => 'admin.tenants.manage',
                'module_id' => $moduleIds['admin'],
                'description' => 'Permissão completa de tenants (todas as ações acima)',
            ],

            // ========== ADMINISTRAÇÃO - USUÁRIOS ==========
            [
                'key' => 'usuario.users.view',
                'module_id' => $moduleIds['usuario'],
                'description' => 'Visualizar lista de usuários',
            ],
            [
                'key' => 'usuario.users.create',
                'module_id' => $moduleIds['usuario'],
                'description' => 'Criar novos usuários',
            ],
            [
                'key' => 'usuario.users.edit',
                'module_id' => $moduleIds['usuario'],
                'description' => 'Editar usuários existentes',
            ],
            [
                'key' => 'usuario.users.delete',
                'module_id' => $moduleIds['usuario'],
                'description' => 'Excluir usuários',
            ],
            [
                'key' => 'usuario.users.manage',
                'module_id' => $moduleIds['usuario'],
                'description' => 'Permissão completa de usuários (todas as ações acima)',
            ],

            // ========== ADMINISTRAÇÃO - MÓDULOS ==========
            [
                'key' => 'admin.modules.view',
                'module_id' => $moduleIds['admin'],
                'description' => 'Visualizar lista de módulos',
            ],
            [
                'key' => 'admin.modules.create',
                'module_id' => $moduleIds['admin'],
                'description' => 'Criar novos módulos',
            ],
            [
                'key' => 'admin.modules.edit',
                'module_id' => $moduleIds['admin'],
                'description' => 'Editar módulos existentes',
            ],
            [
                'key' => 'admin.modules.delete',
                'module_id' => $moduleIds['admin'],
                'description' => 'Excluir módulos',
            ],
            [
                'key' => 'admin.modules.manage',
                'module_id' => $moduleIds['admin'],
                'description' => 'Permissão completa de módulos (todas as ações acima)',
            ],

            // ========== ADMINISTRAÇÃO - PERMISSÕES ==========
            [
                'key' => 'admin.permissions.view',
                'module_id' => $moduleIds['admin'],
                'description' => 'Visualizar lista de permissões',
            ],
            [
                'key' => 'admin.permissions.create',
                'module_id' => $moduleIds['admin'],
                'description' => 'Criar novas permissões',
            ],
            [
                'key' => 'admin.permissions.edit',
                'module_id' => $moduleIds['admin'],
                'description' => 'Editar permissões existentes',
            ],
            [
                'key' => 'admin.permissions.delete',
                'module_id' => $moduleIds['admin'],
                'description' => 'Excluir permissões',
            ],
            [
                'key' => 'admin.permissions.manage',
                'module_id' => $moduleIds['admin'],
                'description' => 'Permissão completa de permissões (todas as ações acima)',
            ],

            // ========== MÓDULO ESTOQUE ==========
            [
                'key' => 'estoque.view',
                'module_id' => $moduleIds['estoque'],
                'description' => 'Visualizar produtos',
            ],
            [
                'key' => 'estoque.create',
                'module_id' => $moduleIds['estoque'],
                'description' => 'Criar novos produtos',
            ],
            [
                'key' => 'estoque.edit',
                'module_id' => $moduleIds['estoque'],
                'description' => 'Editar produtos existentes',
            ],
            [
                'key' => 'estoque.delete',
                'module_id' => $moduleIds['estoque'],
                'description' => 'Excluir produtos',
            ],
            [
                'key' => 'estoque.manage',
                'module_id' => $moduleIds['estoque'],
                'description' => 'Permissão completa de estoque (todas as ações acima)',
            ],
            [
                'key' => 'estoque.export',
                'module_id' => $moduleIds['estoque'],
                'description' => 'Exportar relatórios de estoque',
            ],
            [
                'key' => 'estoque.import',
                'module_id' => $moduleIds['estoque'],
                'description' => 'Importar produtos em lote',
            ],
            [
                'key' => 'estoque.adjust',
                'module_id' => $moduleIds['estoque'],
                'description' => 'Ajustar estoque manualmente',
            ],

            // ========== MÓDULO CLIENTE ==========
            [
                'key' => 'cliente.view',
                'module_id' => $moduleIds['cliente'],
                'description' => 'Visualizar clientes',
            ],
            [
                'key' => 'cliente.create',
                'module_id' => $moduleIds['cliente'],
                'description' => 'Criar novos clientes',
            ],
            [
                'key' => 'cliente.edit',
                'module_id' => $moduleIds['cliente'],
                'description' => 'Editar clientes existentes',
            ],
            [
                'key' => 'cliente.delete',
                'module_id' => $moduleIds['cliente'],
                'description' => 'Excluir clientes',
            ],
            [
                'key' => 'cliente.manage',
                'module_id' => $moduleIds['cliente'],
                'description' => 'Permissão completa de clientes (todas as ações acima)',
            ],
            [
                'key' => 'cliente.view.contacts',
                'module_id' => $moduleIds['cliente'],
                'description' => 'Ver contatos do cliente',
            ],
            [
                'key' => 'cliente.edit.contacts',
                'module_id' => $moduleIds['cliente'],
                'description' => 'Editar contatos do cliente',
            ],
            [
                'key' => 'cliente.view.history',
                'module_id' => $moduleIds['cliente'],
                'description' => 'Ver histórico do cliente',
            ],

            // ========== MÓDULO FINANCEIRO ==========
            [
                'key' => 'financeiro.view',
                'module_id' => $moduleIds['financeiro'],
                'description' => 'Visualizar dashboard financeiro',
            ],
            [
                'key' => 'financeiro.transacoes',
                'module_id' => $moduleIds['financeiro'],
                'description' => 'Ver transações',
            ],
            [
                'key' => 'financeiro.relatorios',
                'module_id' => $moduleIds['financeiro'],
                'description' => 'Ver relatórios financeiros',
            ],
            [
                'key' => 'financeiro.transacoes.create',
                'module_id' => $moduleIds['financeiro'],
                'description' => 'Criar transações',
            ],
            [
                'key' => 'financeiro.transacoes.edit',
                'module_id' => $moduleIds['financeiro'],
                'description' => 'Editar transações',
            ],
            [
                'key' => 'financeiro.transacoes.delete',
                'module_id' => $moduleIds['financeiro'],
                'description' => 'Excluir transações',
            ],
            [
                'key' => 'financeiro.manage',
                'module_id' => $moduleIds['financeiro'],
                'description' => 'Permissão completa financeira (todas as ações acima)',
            ],
            [
                'key' => 'financeiro.approve',
                'module_id' => $moduleIds['financeiro'],
                'description' => 'Aprovar transações',
            ],
            [
                'key' => 'financeiro.cancel',
                'module_id' => $moduleIds['financeiro'],
                'description' => 'Cancelar transações',
            ],
            [
                'key' => 'financeiro.reports.export',
                'module_id' => $moduleIds['financeiro'],
                'description' => 'Exportar relatórios',
            ],

            // ========== MÓDULO ALUNOS ==========
            [
                'key' => 'students.view',
                'module_id' => $moduleIds['students'],
                'description' => 'Visualizar alunos',
            ],
            [
                'key' => 'students.create',
                'module_id' => $moduleIds['students'],
                'description' => 'Criar novos alunos',
            ],
            [
                'key' => 'students.edit',
                'module_id' => $moduleIds['students'],
                'description' => 'Editar alunos existentes',
            ],
            [
                'key' => 'students.delete',
                'module_id' => $moduleIds['students'],
                'description' => 'Excluir alunos',
            ],
            [
                'key' => 'students.manage',
                'module_id' => $moduleIds['students'],
                'description' => 'Permissão completa de alunos (todas as ações acima)',
            ],
            [
                'key' => 'students.export',
                'module_id' => $moduleIds['students'],
                'description' => 'Exportar relatórios de alunos',
            ],
            [
                'key' => 'students.upload_document',
                'module_id' => $moduleIds['students'],
                'description' => 'Upload de documentos de alunos',
            ],

            // ========== MÓDULO ARQUIVOS ==========
            [
                'key' => 'files.view',
                'module_id' => $moduleIds['files'],
                'description' => 'Visualizar arquivos',
            ],
            [
                'key' => 'files.upload',
                'module_id' => $moduleIds['files'],
                'description' => 'Fazer upload de arquivos',
            ],
            [
                'key' => 'files.delete',
                'module_id' => $moduleIds['files'],
                'description' => 'Excluir arquivos',
            ],
            [
                'key' => 'files.download',
                'module_id' => $moduleIds['files'],
                'description' => 'Baixar arquivos',
            ],
            [
                'key' => 'files.manage',
                'module_id' => $moduleIds['files'],
                'description' => 'Permissão completa de arquivos (todas as ações acima)',
            ],
        ];
    }
}

