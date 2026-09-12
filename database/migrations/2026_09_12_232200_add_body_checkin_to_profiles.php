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
            $table->decimal('height_cm', 5, 2)->nullable();
            $table->boolean('body_checkin_pending')->default(false);
            $table->timestampTz('height_prompt_skipped_at')->nullable();
        });

        CheckConstraint::add(
            'profiles',
            'profiles_height_cm_check',
            'height_cm IS NULL OR (height_cm BETWEEN 50 AND 250)',
        );
    }

    public function down(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->dropColumn([
                'height_cm',
                'body_checkin_pending',
                'height_prompt_skipped_at',
            ]);
        });
    }
};
