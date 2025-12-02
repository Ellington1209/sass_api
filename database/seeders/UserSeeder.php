<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'ellington1209@gmail.com'],
            [
                'name' => 'Ellington Machado de Paula',
                'password' => Hash::make('123456'),
                'is_super_admin' => true,
                'tenant_id' => null,
            ]
        );

        User::updateOrCreate(
            ['email' => 'mara@example.com'],
            [
                'name' => 'Mara Lidia Araujo Chaves',
                'password' => Hash::make('123456'),
                'is_super_admin' => false,
                'tenant_id' => null,
            ]
        );
        User::updateOrCreate(
            ['email' => 'joao@example.com'],
            [
                'name' => 'João da Silva',
                'password' => Hash::make('123456'),
                'is_super_admin' => false,
                'tenant_id' => null,
            ]
        );

        $this->command->info('Usuários criados com sucesso!');
    }
}
