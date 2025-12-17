<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('providers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('person_id')->constrained('persons')->onDelete('cascade');
            $table->json('service_ids')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'person_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('providers');
    }
};

