<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'Ellington Machado de Paula',
            'email' => 'ellington1209@gmail.com',
            'password' => Hash::make('Tonemara89'),
            'is_super_admin' => true,
            'tenant_id' => null,
        ]);

        $this->command->info('Super admin user created successfully!');
        $this->command->info('Email: ellington@admin.com');
        $this->command->info('Password: Tonemara89');
    }
}

