<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('medicine_id')->constrained()->restrictOnDelete();
            $table->string('batch_number', 50);
            $table->date('expiry_date');
            $table->unsignedInteger('initial_quantity');
            $table->unsignedInteger('current_quantity');
            $table->timestamps();

            $table->unique(['medicine_id', 'batch_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('batches');
    }
};
