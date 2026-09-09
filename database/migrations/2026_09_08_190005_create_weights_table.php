<?php

use App\Support\CheckConstraint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('weights', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profile_id')->constrained()->cascadeOnDelete();
            $table->decimal('kg', 5, 2);
            $table->timestampTz('measured_at');
            $table->timestamps();
        });

        CheckConstraint::add('weights', 'weights_kg_check', 'kg BETWEEN 10 AND 400');
    }

    public function down(): void
    {
        Schema::dropIfExists('weights');
    }
};
