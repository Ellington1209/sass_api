<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('provider_id')->constrained('providers')->onDelete('restrict');
            $table->foreignId('transaction_id')->constrained('financial_transactions')->onDelete('restrict');
            $table->string('reference_type');
            $table->bigInteger('reference_id');
            $table->decimal('base_amount', 10, 2);
            $table->decimal('commission_amount', 10, 2);
            $table->enum('status', ['PENDING', 'PAID', 'CANCELLED'])->default('PENDING');
            $table->dateTime('paid_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Índices para melhorar performance
            $table->index(['tenant_id', 'provider_id', 'status']);
            $table->index(['tenant_id', 'status']);
            $table->index(['reference_type', 'reference_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commissions');
    }
};

