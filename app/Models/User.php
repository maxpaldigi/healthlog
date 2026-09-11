<?php

namespace App\Models;

use App\Enums\ConsentType;
use App\Enums\ProfileRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string|null $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property bool $is_super_admin
 * @property-read int $profiles_count
 * @property Carbon|null $onboarding_completed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token', 'is_super_admin'])]
class User extends Authenticatable implements PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
            'onboarding_completed_at' => 'datetime',
            'is_super_admin' => 'boolean',
        ];
    }

    public function isSuperAdmin(): bool
    {
        return $this->is_super_admin === true;
    }

    public function hasGrantedConsent(ConsentType $type): bool
    {
        return $this->consents()
            ->where('type', $type)
            ->whereNull('revoked_at')
            ->exists();
    }

    /**
     * @return HasMany<Consent, $this>
     */
    public function consents(): HasMany
    {
        return $this->hasMany(Consent::class);
    }

    /**
     * @return HasMany<Profile, $this>
     */
    public function profiles(): HasMany
    {
        return $this->hasMany(Profile::class);
    }

    /**
     * @return HasMany<ProfileMember, $this>
     */
    public function memberships(): HasMany
    {
        return $this->hasMany(ProfileMember::class);
    }

    /**
     * Profiles this user may open after accepting an invite, plus profiles they own.
     *
     * @return BelongsToMany<Profile, $this>
     */
    public function accessibleProfiles(): BelongsToMany
    {
        return $this->belongsToMany(Profile::class, 'profile_members')
            ->withPivot(['role', 'invited_at', 'accepted_at'])
            ->withTimestamps()
            ->whereNotNull('profile_members.accepted_at');
    }

    public function canAccessProfile(Profile|int $profile): bool
    {
        $id = $profile instanceof Profile ? $profile->id : $profile;

        return $this->memberships()
            ->accepted()
            ->where('profile_id', $id)
            ->exists();
    }

    public function isOwnerOf(Profile $profile): bool
    {
        return $this->roleOn($profile) === ProfileRole::Owner;
    }

    public function roleOn(Profile $profile): ?ProfileRole
    {
        $role = $this->memberships()
            ->accepted()
            ->where('profile_id', $profile->id)
            ->value('role');

        return is_string($role) ? ProfileRole::tryFrom($role) : ($role instanceof ProfileRole ? $role : null);
    }

    /**
     * @return HasOne<Profile, $this>
     */
    public function defaultProfile(): HasOne
    {
        return $this->hasOne(Profile::class)->where('is_default', true);
    }

    /**
     * @return HasMany<LoginToken, $this>
     */
    public function loginTokens(): HasMany
    {
        return $this->hasMany(LoginToken::class);
    }
}
