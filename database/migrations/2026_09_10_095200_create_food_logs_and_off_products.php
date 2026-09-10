<?php

use App\Support\CheckConstraint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('off_products', function (Blueprint $table) {
            $table->id();
            $table->string('barcode', 20)->unique();
            $table->string('name');
            $table->string('brands')->nullable();
            $table->decimal('sodium_mg_100g', 8, 2)->nullable();
            $table->decimal('sugars_g_100g', 8, 2)->nullable();
            $table->decimal('carbs_g_100g', 8, 2)->nullable();
            $table->string('nutriscore', 1)->nullable();
            $table->decimal('serving_g', 8, 2)->nullable();
            $table->timestampTz('fetched_at');
            $table->timestamps();
        });

        Schema::create('food_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profile_id')->constrained()->cascadeOnDelete();
            $table->string('source');
            $table->string('source_ref', 20)->nullable();
            $table->string('label');
            $table->decimal('portion_g', 8, 2);
            $table->string('portion_label');
            $table->timestampTz('eaten_at');
            $table->foreignId('glucose_reading_id')->nullable()->constrained('glucose_readings')->nullOnDelete();
            $table->decimal('sodium_mg_100g', 8, 2)->nullable();
            $table->decimal('sugars_g_100g', 8, 2)->nullable();
            $table->decimal('carbs_g_100g', 8, 2)->nullable();
            $table->string('nutriscore', 1)->nullable();
            $table->timestamps();
        });

        CheckConstraint::add('food_logs', 'food_logs_portion_g_check', 'portion_g BETWEEN 1 AND 2000');
    }

    public function down(): void
    {
        Schema::dropIfExists('food_logs');
        Schema::dropIfExists('off_products');
    }
};
