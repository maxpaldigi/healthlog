<?php

use App\Support\CheckConstraint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->decimal('glucose_low_mg_dl', 6, 2)->nullable();
            $table->decimal('glucose_high_mg_dl', 6, 2)->nullable();
            $table->boolean('reminders_enabled')->default(false);
            $table->string('reminder_morning', 5)->default('08:00');
            $table->string('reminder_afternoon', 5)->default('14:00');
            $table->string('reminder_evening', 5)->default('20:00');
        });

        CheckConstraint::add(
            'profiles',
            'profiles_glucose_targets_check',
            '(glucose_low_mg_dl IS NULL OR (glucose_low_mg_dl BETWEEN 20 AND 700)) AND (glucose_high_mg_dl IS NULL OR (glucose_high_mg_dl BETWEEN 20 AND 700)) AND (glucose_low_mg_dl IS NULL OR glucose_high_mg_dl IS NULL OR glucose_low_mg_dl < glucose_high_mg_dl)',
        );

        Schema::create('share_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profile_id')->constrained()->cascadeOnDelete();
            $table->string('token', 64)->unique();
            $table->date('starts_on');
            $table->date('ends_on');
            $table->timestampTz('expires_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('share_links');

        Schema::table('profiles', function (Blueprint $table) {
            $table->dropColumn([
                'glucose_low_mg_dl',
                'glucose_high_mg_dl',
                'reminders_enabled',
                'reminder_morning',
                'reminder_afternoon',
                'reminder_evening',
            ]);
        });
    }
};
