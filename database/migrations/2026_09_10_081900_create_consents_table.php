<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->timestampTz('granted_at');
            $table->timestampTz('revoked_at')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('policy_version');
            $table->timestamps();

            $table->index(['user_id', 'type', 'revoked_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consents');
    }
};
