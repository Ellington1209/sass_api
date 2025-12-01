<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StatusStudentSeeder extends Seeder
{
    public function run(): void
    {
        $statuses = [
            [
                'key' => 'pre-cadastro',
                'name' => 'Pré-Cadastro',
                'description' => 'Aluno em processo de cadastro inicial',
                'order' => 1,
                'active' => true,
            ],
            [
                'key' => 'matriculado',
                'name' => 'Matriculado',
                'description' => 'Aluno matriculado no curso',
                'order' => 2,
                'active' => true,
            ],
            [
                'key' => 'em-aulas-teoricas',
                'name' => 'Em Aulas Teóricas',
                'description' => 'Aluno frequentando aulas teóricas',
                'order' => 3,
                'active' => true,
            ],
            [
                'key' => 'aguardando-prova-teorica',
                'name' => 'Aguardando Prova Teórica',
                'description' => 'Aluno aguardando realização da prova teórica',
                'order' => 4,
                'active' => true,
            ],
            [
                'key' => 'reprovado-teorica',
                'name' => 'Reprovado (Teórica)',
                'description' => 'Aluno reprovado na prova teórica',
                'order' => 5,
                'active' => true,
            ],
            [
                'key' => 'aprovado-teorica',
                'name' => 'Aprovado (Teórica)',
                'description' => 'Aluno aprovado na prova teórica',
                'order' => 6,
                'active' => true,
            ],
            [
                'key' => 'em-aulas-praticas',
                'name' => 'Em Aulas Práticas',
                'description' => 'Aluno frequentando aulas práticas',
                'order' => 7,
                'active' => true,
            ],
            [
                'key' => 'aguardando-exame-pratico',
                'name' => 'Aguardando Exame Prático',
                'description' => 'Aluno aguardando realização do exame prático',
                'order' => 8,
                'active' => true,
            ],
            [
                'key' => 'reprovado-pratico',
                'name' => 'Reprovado (Prático)',
                'description' => 'Aluno reprovado no exame prático',
                'order' => 9,
                'active' => true,
            ],
            [
                'key' => 'aprovado-cnh-pronta',
                'name' => 'Aprovado - CNH Pronta',
                'description' => 'Aluno aprovado e CNH pronta para retirada',
                'order' => 10,
                'active' => true,
            ],
        ];

        foreach ($statuses as $status) {
            DB::table('status_students')->updateOrInsert(
                ['key' => $status['key']],
                $status
            );
        }

        $this->command->info('Status de alunos criados com sucesso!');
    }
}

