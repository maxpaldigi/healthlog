<?php

use App\Support\CheckConstraint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bp_readings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profile_id')->constrained()->cascadeOnDelete();
            $table->smallInteger('systolic');
            $table->smallInteger('diastolic');
            $table->smallInteger('pulse')->nullable();
            $table->timestampTz('measured_at');
            $table->string('arm')->default('unknown');
            $table->string('body_position')->default('sitting');
            $table->string('tag')->default('none');
            $table->text('note')->nullable();
            $table->string('source')->default('manual');
            $table->timestamps();

            $table->index(['profile_id', 'measured_at']);
        });

        CheckConstraint::add('bp_readings', 'bp_readings_systolic_check', 'systolic BETWEEN 60 AND 300');
        CheckConstraint::add('bp_readings', 'bp_readings_diastolic_check', 'diastolic BETWEEN 30 AND 200');
        CheckConstraint::add('bp_readings', 'bp_readings_systolic_gt_diastolic_check', 'systolic > diastolic');
        CheckConstraint::add('bp_readings', 'bp_readings_pulse_check', 'pulse IS NULL OR pulse BETWEEN 30 AND 220');
        CheckConstraint::add('bp_readings', 'bp_readings_arm_check', "arm IN ('left', 'right', 'unknown')");
        CheckConstraint::add('bp_readings', 'bp_readings_body_position_check', "body_position IN ('sitting', 'standing', 'lying')");
        CheckConstraint::add('bp_readings', 'bp_readings_tag_check', "tag IN ('none', 'before_meds', 'after_meds', 'after_exercise', 'stressed')");
        CheckConstraint::add('bp_readings', 'bp_readings_source_check', "source IN ('manual', 'photo', 'voice', 'import')");
    }

    public function down(): void
    {
        Schema::dropIfExists('bp_readings');
    }
};
