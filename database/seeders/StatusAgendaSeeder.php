<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StatusAgendaSeeder extends Seeder
{
    public function run(): void
    {
        $statuses = [
            [
                'key' => 'agendado',
                'name' => 'Agendado',
                'description' => 'Agendamento confirmado',
                'order' => 1,
                'active' => true,
            ],
            [
                'key' => 'confirmado',
                'name' => 'Confirmado',
                'description' => 'Agendamento confirmado pelo cliente',
                'order' => 2,
                'active' => true,
            ],
            [
                'key' => 'em-andamento',
                'name' => 'Em Andamento',
                'description' => 'Serviço em execução',
                'order' => 3,
                'active' => true,
            ],
            [
                'key' => 'concluido',
                'name' => 'Concluído',
                'description' => 'Serviço finalizado com sucesso',
                'order' => 4,
                'active' => true,
            ],
            [
                'key' => 'cancelado',
                'name' => 'Cancelado',
                'description' => 'Agendamento cancelado',
                'order' => 5,
                'active' => true,
            ],
            [
                'key' => 'nao-compareceu',
                'name' => 'Não Compareceu',
                'description' => 'Cliente não compareceu ao agendamento',
                'order' => 6,
                'active' => true,
            ],
        ];

        foreach ($statuses as $status) {
            DB::table('status_agenda')->updateOrInsert(
                ['key' => $status['key']],
                $status
            );
        }

        $this->command->info('Status de agenda criados com sucesso!');
    }
}

