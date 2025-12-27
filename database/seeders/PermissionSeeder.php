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
        $requiredModules = ['admin', 'cliente', 'estoque', 'financeiro', 'usuario', 'students', 'files', 'agenda', 'auto-escola', 'barbearia', 'salão-de-beleza', 'service', 'provider', 'whatsapp'];
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

            // ========== ADMINISTRAÇÃO - USUARIOS ==========
            [
                'key' => 'admin.users.view',
                'module_id' => $moduleIds['admin'],
                'description' => 'Visualizar lista de usuários',
            ],
            [
                'key' => 'admin.users.create',
                'module_id' => $moduleIds['admin'],
                'description' => 'Criar novos usuários',
            ],
            [
                'key' => 'admin.users.edit',
                'module_id' => $moduleIds['admin'],
                'description' => 'Editar usuários existentes',
            ],
            [
                'key' => 'admin.users.delete',
                'module_id' => $moduleIds['admin'],
                'description' => 'Excluir usuários',
            ],
            [
                'key' => 'admin.users.manage',
                'module_id' => $moduleIds['admin'],
                'description' => 'Permissão completa de usuários (todas as ações acima)',
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
            // Dashboard e Visualizações
            [
                'key' => 'financeiro.view',
                'module_id' => $moduleIds['financeiro'],
                'description' => 'Visualizar dashboard financeiro',
            ],
            [
                'key' => 'financeiro.reports.view',
                'module_id' => $moduleIds['financeiro'],
                'description' => 'Ver relatórios financeiros',
            ],
            [
                'key' => 'financeiro.reports.export',
                'module_id' => $moduleIds['financeiro'],
                'description' => 'Exportar relatórios financeiros',
            ],

            // Transações
            [
                'key' => 'financeiro.transactions.view',
                'module_id' => $moduleIds['financeiro'],
                'description' => 'Visualizar transações',
            ],
            [
                'key' => 'financeiro.transactions.create',
                'module_id' => $moduleIds['financeiro'],
                'description' => 'Criar transações',
            ],
            [
                'key' => 'financeiro.transactions.edit',
                'module_id' => $moduleIds['financeiro'],
                'description' => 'Editar transações',
            ],
            [
                'key' => 'financeiro.transactions.delete',
                'module_id' => $moduleIds['financeiro'],
                'description' => 'Excluir transações',
            ],
            [
                'key' => 'financeiro.transactions.cancel',
                'module_id' => $moduleIds['financeiro'],
                'description' => 'Cancelar transações',
            ],

            // Comissões
            [
                'key' => 'financeiro.commissions.view',
                'module_id' => $moduleIds['financeiro'],
                'description' => 'Visualizar comissões',
            ],
            [
                'key' => 'financeiro.commissions.pay',
                'module_id' => $moduleIds['financeiro'],
                'description' => 'Pagar comissões',
            ],
            [
                'key' => 'financeiro.commissions.cancel',
                'module_id' => $moduleIds['financeiro'],
                'description' => 'Cancelar comissões',
            ],

            // Configurações Financeiras - Categorias
            [
                'key' => 'financeiro.categories.view',
                'module_id' => $moduleIds['financeiro'],
                'description' => 'Visualizar categorias financeiras',
            ],
            [
                'key' => 'financeiro.categories.create',
                'module_id' => $moduleIds['financeiro'],
                'description' => 'Criar categorias financeiras',
            ],
            [
                'key' => 'financeiro.categories.edit',
                'module_id' => $moduleIds['financeiro'],
                'description' => 'Editar categorias financeiras',
            ],
            [
                'key' => 'financeiro.categories.delete',
                'module_id' => $moduleIds['financeiro'],
                'description' => 'Excluir categorias financeiras',
            ],

            // Configurações Financeiras - Métodos de Pagamento
            [
                'key' => 'financeiro.payment_methods.view',
                'module_id' => $moduleIds['financeiro'],
                'description' => 'Visualizar métodos de pagamento',
            ],
            [
                'key' => 'financeiro.payment_methods.create',
                'module_id' => $moduleIds['financeiro'],
                'description' => 'Criar métodos de pagamento',
            ],
            [
                'key' => 'financeiro.payment_methods.edit',
                'module_id' => $moduleIds['financeiro'],
                'description' => 'Editar métodos de pagamento',
            ],
            [
                'key' => 'financeiro.payment_methods.delete',
                'module_id' => $moduleIds['financeiro'],
                'description' => 'Excluir métodos de pagamento',
            ],

            // Configurações de Comissão
            [
                'key' => 'financeiro.commission_configs.view',
                'module_id' => $moduleIds['financeiro'],
                'description' => 'Visualizar configurações de comissão',
            ],
            [
                'key' => 'financeiro.commission_configs.create',
                'module_id' => $moduleIds['financeiro'],
                'description' => 'Criar configurações de comissão',
            ],
            [
                'key' => 'financeiro.commission_configs.edit',
                'module_id' => $moduleIds['financeiro'],
                'description' => 'Editar configurações de comissão',
            ],
            [
                'key' => 'financeiro.commission_configs.delete',
                'module_id' => $moduleIds['financeiro'],
                'description' => 'Excluir configurações de comissão',
            ],

            // Permissão Completa
            [
                'key' => 'financeiro.manage',
                'module_id' => $moduleIds['financeiro'],
                'description' => 'Permissão completa no módulo financeiro (todas as ações acima)',
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
            // ========== MÓDULO CONFIGURAÇÕES ==========
            [
                'key' => 'settings.view',
                'module_id' => $moduleIds['settings'],
                'description' => 'Visualizar configurações',
            ],
            [
                'key' => 'settings.edit',
                'module_id' => $moduleIds['settings'],
                'description' => 'Editar configurações',
            ],
            [
                'key' => 'settings.manage',
                'module_id' => $moduleIds['settings'],
                'description' => 'Permissão completa de configurações (todas as ações acima)',
            ],
            [
                'key' => 'settings.delete',
                'module_id' => $moduleIds['settings'],
                'description' => 'Excluir configurações',
            ],

            // ========== MÓDULO AGENDA ==========
            [
                'key' => 'agenda.view',
                'module_id' => $moduleIds['agenda'],
                'description' => 'Visualizar agenda',
            ],
            [
                'key' => 'agenda.create',
                'module_id' => $moduleIds['agenda'],
                'description' => 'Criar novos agenda',
            ],
            [
                'key' => 'agenda.edit',
                'module_id' => $moduleIds['agenda'],
                'description' => 'Editar agenda existentes',
            ],
            [
                'key' => 'agenda.delete',
                'module_id' => $moduleIds['agenda'],
                'description' => 'Excluir agenda',
            ],
            [
                'key' => 'agenda.providers.view',
                'module_id' => $moduleIds['agenda'],
                'description' => 'Visualizar provedores na agenda',
            ],
            [
                'key' => 'agenda.providers.create',
                'module_id' => $moduleIds['agenda'],
                'description' => 'Criar provedores na agenda',
            ],
            [
                'key' => 'agenda.providers.edit',
                'module_id' => $moduleIds['agenda'],
                'description' => 'Editar provedores na agenda',
            ],
            [
                'key' => 'agenda.providers.delete',
                'module_id' => $moduleIds['agenda'],
                'description' => 'Excluir provedores na agenda',
            ],
            [
                'key' => 'agenda.appointments.view',
                'module_id' => $moduleIds['agenda'],
                'description' => 'Visualizar agendamentos',
            ],
            [
                'key' => 'agenda.appointments.create',
                'module_id' => $moduleIds['agenda'],
                'description' => 'Criar agendamentos',
            ],
            [
                'key' => 'agenda.appointments.edit',
                'module_id' => $moduleIds['agenda'],
                'description' => 'Editar agendamentos',
            ],
            [
                'key' => 'agenda.appointments.delete',
                'module_id' => $moduleIds['agenda'],
                'description' => 'Excluir agendamentos',
            ],
            [
                'key' => 'agenda.services.view',
                'module_id' => $moduleIds['agenda'],
                'description' => 'Visualizar serviços na agenda',
            ],
            [
                'key' => 'agenda.services.create',
                'module_id' => $moduleIds['agenda'],
                'description' => 'Criar serviços na agenda',
            ],
            [
                'key' => 'agenda.services.edit',
                'module_id' => $moduleIds['agenda'],
                'description' => 'Editar serviços na agenda',
            ],
            [
                'key' => 'agenda.services.delete',
                'module_id' => $moduleIds['agenda'],
                'description' => 'Excluir serviços na agenda',
            ],

            // ========== MÓDULO AUTO-ESCOLA ==========
            [
                'key' => 'auto-escola.view',
                'module_id' => $moduleIds['auto-escola'],
                'description' => 'Visualizar dados de auto escola',
            ],
            [
                'key' => 'auto-escola.create',
                'module_id' => $moduleIds['auto-escola'],
                'description' => 'Criar dados de auto escola',
            ],
            [
                'key' => 'auto-escola.edit',
                'module_id' => $moduleIds['auto-escola'],
                'description' => 'Editar dados de auto escola',
            ],
            [
                'key' => 'auto-escola.delete',
                'module_id' => $moduleIds['auto-escola'],
                'description' => 'Excluir dados de auto escola',
            ],
            [
                'key' => 'auto-escola.manage',
                'module_id' => $moduleIds['auto-escola'],
                'description' => 'Permissão completa de auto escola (todas as ações acima)',
            ],

            // ========== MÓDULO BARBEARIA ==========
            [
                'key' => 'barbearia.view',
                'module_id' => $moduleIds['barbearia'],
                'description' => 'Visualizar dados de barbearia',
            ],
            [
                'key' => 'barbearia.create',
                'module_id' => $moduleIds['barbearia'],
                'description' => 'Criar dados de barbearia',
            ],
            [
                'key' => 'barbearia.edit',
                'module_id' => $moduleIds['barbearia'],
                'description' => 'Editar dados de barbearia',
            ],
            [
                'key' => 'barbearia.delete',
                'module_id' => $moduleIds['barbearia'],
                'description' => 'Excluir dados de barbearia',
            ],
            [
                'key' => 'barbearia.manage',
                'module_id' => $moduleIds['barbearia'],
                'description' => 'Permissão completa de barbearia (todas as ações acima)',
            ],

            // ========== MÓDULO SALÃO DE BELEZA ==========
            [
                'key' => 'salão-de-beleza.view',
                'module_id' => $moduleIds['salão-de-beleza'],
                'description' => 'Visualizar dados de salão de beleza',
            ],
            [
                'key' => 'salão-de-beleza.create',
                'module_id' => $moduleIds['salão-de-beleza'],
                'description' => 'Criar dados de salão de beleza',
            ],
            [
                'key' => 'salão-de-beleza.edit',
                'module_id' => $moduleIds['salão-de-beleza'],
                'description' => 'Editar dados de salão de beleza',
            ],
            [
                'key' => 'salão-de-beleza.delete',
                'module_id' => $moduleIds['salão-de-beleza'],
                'description' => 'Excluir dados de salão de beleza',
            ],
            [
                'key' => 'salão-de-beleza.manage',
                'module_id' => $moduleIds['salão-de-beleza'],
                'description' => 'Permissão completa de salão de beleza (todas as ações acima)',
            ],

            // ========== MÓDULO SERVICE ==========
            [
                'key' => 'service.view',
                'module_id' => $moduleIds['service'],
                'description' => 'Visualizar serviços',
            ],
            [
                'key' => 'service.create',
                'module_id' => $moduleIds['service'],
                'description' => 'Criar serviços',
            ],
            [
                'key' => 'service.edit',
                'module_id' => $moduleIds['service'],
                'description' => 'Editar serviços',
            ],
            [
                'key' => 'service.delete',
                'module_id' => $moduleIds['service'],
                'description' => 'Excluir serviços',
            ],
            [
                'key' => 'service.manage',
                'module_id' => $moduleIds['service'],
                'description' => 'Permissão completa de serviços (todas as ações acima)',
            ],

            // ========== MÓDULO PROVIDER ==========
            [
                'key' => 'provider.view',
                'module_id' => $moduleIds['provider'],
                'description' => 'Visualizar profissionais',
            ],
            [
                'key' => 'provider.create',
                'module_id' => $moduleIds['provider'],
                'description' => 'Criar profissionais',
            ],
            [
                'key' => 'provider.edit',
                'module_id' => $moduleIds['provider'],
                'description' => 'Editar profissionais',
            ],
            [
                'key' => 'provider.delete',
                'module_id' => $moduleIds['provider'],
                'description' => 'Excluir profissionais',
            ],
            [
                'key' => 'provider.manage',
                'module_id' => $moduleIds['provider'],
                'description' => 'Permissão completa de profissionais (todas as ações acima)',
            ],

            // ========== MÓDULO WHATSAPP ==========
            [
                'key' => 'whatsapp.instances.view',
                'module_id' => $moduleIds['whatsapp'],
                'description' => 'Visualizar instâncias do WhatsApp',
            ],
            [
                'key' => 'whatsapp.instances.create',
                'module_id' => $moduleIds['whatsapp'],
                'description' => 'Criar instâncias do WhatsApp',
            ],
            [
                'key' => 'whatsapp.instances.send',
                'module_id' => $moduleIds['whatsapp'],
                'description' => 'Enviar mensagens via WhatsApp',
            ],
            [
                'key' => 'whatsapp.instances.delete',
                'module_id' => $moduleIds['whatsapp'],
                'description' => 'Deletar instâncias do WhatsApp',
            ],
            [
                'key' => 'whatsapp.instances.manage',
                'module_id' => $moduleIds['whatsapp'],
                'description' => 'Permissão completa de WhatsApp (todas as ações acima)',
            ],
        ];
    }
}

