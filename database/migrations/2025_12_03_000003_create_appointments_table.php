<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('service_id')->constrained('services')->onDelete('cascade');
            $table->foreignId('provider_id')->constrained('providers')->onDelete('cascade');
            $table->foreignId('client_id')->constrained('users')->onDelete('cascade');
            $table->dateTime('date_start');
            $table->dateTime('date_end');
            $table->foreignId('status_agenda_id')->nullable()->constrained('status_agenda')->onDelete('set null');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'provider_id', 'date_start']);
            $table->index(['tenant_id', 'date_start', 'date_end']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};

