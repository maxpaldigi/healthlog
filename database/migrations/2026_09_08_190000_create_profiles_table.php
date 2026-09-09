<?php

use App\Support\CheckConstraint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->date('date_of_birth')->nullable();
            $table->string('timezone')->default('Asia/Kolkata');
            $table->string('glucose_unit')->default('mg_dl');
            $table->string('weight_unit')->default('kg');
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        CheckConstraint::add('profiles', 'profiles_glucose_unit_check', "glucose_unit IN ('mg_dl', 'mmol_l')");
        CheckConstraint::add('profiles', 'profiles_weight_unit_check', "weight_unit IN ('kg', 'lb')");
    }

    public function down(): void
    {
        Schema::dropIfExists('profiles');
    }
};
