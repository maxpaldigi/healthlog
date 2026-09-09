<?php

use App\Support\CheckConstraint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('glucose_readings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profile_id')->constrained()->cascadeOnDelete();
            $table->decimal('value_mg_dl', 5, 2);
            $table->string('context');
            $table->timestampTz('measured_at');
            $table->text('note')->nullable();
            $table->string('source')->default('manual');
            $table->timestamps();

            $table->index(['profile_id', 'measured_at']);
        });

        CheckConstraint::add('glucose_readings', 'glucose_readings_value_check', 'value_mg_dl BETWEEN 20 AND 700');
        CheckConstraint::add('glucose_readings', 'glucose_readings_context_check', "context IN ('fasting', 'pre_meal', 'post_meal_1h', 'post_meal_2h', 'bedtime', 'random')");
        CheckConstraint::add('glucose_readings', 'glucose_readings_source_check', "source IN ('manual', 'photo', 'voice', 'import')");
    }

    public function down(): void
    {
        Schema::dropIfExists('glucose_readings');
    }
};
