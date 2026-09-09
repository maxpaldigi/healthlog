<?php

use App\Support\CheckConstraint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hba1c_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profile_id')->constrained()->cascadeOnDelete();
            $table->decimal('value', 4, 2);
            $table->date('tested_at');
            $table->string('lab_name')->nullable();
            $table->timestamps();
        });

        CheckConstraint::add('hba1c_results', 'hba1c_results_value_check', 'value BETWEEN 3 AND 20');
    }

    public function down(): void
    {
        Schema::dropIfExists('hba1c_results');
    }
};
