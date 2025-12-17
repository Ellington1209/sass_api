<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(UserSeeder::class);
        $this->call(ModuleSeeder::class);
        $this->call(StatusStudentSeeder::class);
        $this->call(StatusAgendaSeeder::class);
        $this->call(PermissionSeeder::class);
        $this->call(ServiceSeeder::class);
    }
}

