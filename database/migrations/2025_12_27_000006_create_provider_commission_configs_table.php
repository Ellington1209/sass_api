<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('provider_commission_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('provider_id')->constrained('providers')->onDelete('cascade');
            $table->foreignId('origin_id')->nullable()->constrained('financial_origins')->onDelete('cascade');
            $table->decimal('commission_rate', 5, 2);
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            // Garantir unicidade: um provider só pode ter uma config por origin
            $table->unique(['tenant_id', 'provider_id', 'origin_id'], 'unique_provider_origin_config');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provider_commission_configs');
    }
};

