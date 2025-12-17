<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tenant_id')
                ->constrained('tenants')
                ->onDelete('cascade');

            $table->foreignId('person_id')
                ->constrained('persons')
                ->onDelete('cascade');

            $table->enum('category', ['A', 'B', 'C', 'D', 'AB', 'AC', 'AD', 'AE'])
                ->nullable();

            $table->foreignId('status_students_id')
                ->nullable()
                ->constrained('status_students')
                ->onDelete('set null');

            $table->string('registration_number')->nullable(); // matrícula

            $table->timestamps();

            $table->unique(['tenant_id', 'person_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
