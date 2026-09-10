<?php

use App\Support\CheckConstraint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('share_links', function (Blueprint $table) {
            $table->string('scope', 16)->default('read')->after('token');
        });

        Schema::table('profiles', function (Blueprint $table) {
            $table->string('glucose_target_set_by', 16)->default('self')->after('glucose_high_mg_dl');
        });

        Schema::create('clinician_targets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('share_link_id')->unique()->constrained()->cascadeOnDelete();
            $table->text('instruction')->nullable();
            $table->decimal('glucose_low_mg_dl', 6, 2)->nullable();
            $table->decimal('glucose_high_mg_dl', 6, 2)->nullable();
            $table->timestampTz('acknowledged_at')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();
        });

        CheckConstraint::add(
            'clinician_targets',
            'clinician_targets_glucose_check',
            '(glucose_low_mg_dl IS NULL OR (glucose_low_mg_dl BETWEEN 20 AND 700)) AND (glucose_high_mg_dl IS NULL OR (glucose_high_mg_dl BETWEEN 20 AND 700)) AND (glucose_low_mg_dl IS NULL OR glucose_high_mg_dl IS NULL OR glucose_low_mg_dl < glucose_high_mg_dl)',
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('clinician_targets');

        Schema::table('profiles', function (Blueprint $table) {
            $table->dropColumn('glucose_target_set_by');
        });

        Schema::table('share_links', function (Blueprint $table) {
            $table->dropColumn('scope');
        });
    }
};
