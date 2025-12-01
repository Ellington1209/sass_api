<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->after('tenant_id')->constrained('users')->onDelete('cascade');
            $table->string('cpf', 14)->unique();
            $table->string('rg', 20)->nullable();
            $table->date('birth_date');
            $table->string('phone', 20)->nullable();
            $table->string('address_street')->nullable();
            $table->string('address_number')->nullable();
            $table->string('address_neighborhood')->nullable();
            $table->string('address_city')->nullable();
            $table->string('address_state', 2)->nullable();
            $table->string('address_zip', 10)->nullable();
            $table->enum('category', ['A', 'B', 'C', 'D', 'AB', 'AC', 'AD', 'AE'])->nullable();
            $table->foreignId('status_students_id')->nullable()->constrained('status_students')->onDelete('set null');
            $table->string('photo_url')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};

