<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('professional_availabilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_id')->constrained('providers')->onDelete('cascade');
            $table->integer('weekday')->comment('0 = domingo, 1 = segunda, ..., 6 = sábado');
            $table->time('start_time');
            $table->time('end_time');
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->unique(['provider_id', 'weekday']);
            $table->index(['provider_id', 'active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('professional_availabilities');
    }
};

