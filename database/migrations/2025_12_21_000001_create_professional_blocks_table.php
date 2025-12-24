<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('professional_blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_id')->constrained('providers')->onDelete('cascade');
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->dateTime('start_at');
            $table->dateTime('end_at');
            $table->string('reason')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->index(['provider_id', 'start_at', 'end_at']);
            $table->index(['tenant_id', 'start_at', 'end_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('professional_blocks');
    }
};

