<?php

use App\Enums\ProfileRole;
use App\Models\Profile;
use App\Models\ProfileMember;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profile_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('email');
            $table->string('role');
            $table->timestampTz('invited_at');
            $table->timestampTz('accepted_at')->nullable();
            $table->timestamps();

            $table->unique(['profile_id', 'user_id']);
            $table->unique(['profile_id', 'email']);
        });

        Schema::table('profiles', function (Blueprint $table) {
            $table->unsignedTinyInteger('alert_inactivity_days')->default(3);
            $table->unsignedTinyInteger('alert_streak')->default(3);
        });

        Schema::table('push_subscriptions', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('profile_id')->constrained()->nullOnDelete();
        });

        Profile::query()->with('user')->each(function (Profile $profile): void {
            $user = $profile->user;

            if ($user === null) {
                return;
            }

            ProfileMember::query()->firstOrCreate(
                [
                    'profile_id' => $profile->id,
                    'user_id' => $user->id,
                ],
                [
                    'email' => mb_strtolower($user->email),
                    'role' => ProfileRole::Owner->value,
                    'invited_at' => $profile->created_at ?? now(),
                    'accepted_at' => $profile->created_at ?? now(),
                ],
            );
        });
    }

    public function down(): void
    {
        Schema::table('push_subscriptions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
        });

        Schema::table('profiles', function (Blueprint $table) {
            $table->dropColumn(['alert_inactivity_days', 'alert_streak']);
        });

        Schema::dropIfExists('profile_members');
    }
};
