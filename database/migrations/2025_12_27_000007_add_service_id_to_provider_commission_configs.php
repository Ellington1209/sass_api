<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('provider_commission_configs', function (Blueprint $table) {
            // Remove a constraint única antiga
            $table->dropUnique('unique_provider_origin_config');
            
            // Adiciona service_id
            $table->foreignId('service_id')->nullable()->after('provider_id')->constrained('services')->onDelete('cascade');
            
            // Nova constraint única: provider + service + origin (permitindo NULLs)
            // Um provider pode ter uma config por combinação de service/origin
            $table->unique(['tenant_id', 'provider_id', 'service_id', 'origin_id'], 'unique_provider_service_origin_config');
        });
    }

    public function down(): void
    {
        Schema::table('provider_commission_configs', function (Blueprint $table) {
            // Remove a nova constraint
            $table->dropUnique('unique_provider_service_origin_config');
            
            // Remove service_id
            $table->dropForeign(['service_id']);
            $table->dropColumn('service_id');
            
            // Restaura a constraint antiga
            $table->unique(['tenant_id', 'provider_id', 'origin_id'], 'unique_provider_origin_config');
        });
    }
};

