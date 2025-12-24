<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_business_hours', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->integer('weekday')->comment('0 = domingo, 1 = segunda, ..., 6 = sábado');
            $table->time('start_time');
            $table->time('end_time');
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->unique(['tenant_id', 'weekday']);
            $table->index(['tenant_id', 'active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_business_hours');
    }
};

