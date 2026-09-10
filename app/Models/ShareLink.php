<?php

namespace App\Models;

use App\Enums\ShareLinkScope;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property int $profile_id
 * @property string $token
 * @property ShareLinkScope $scope
 * @property Carbon $starts_on
 * @property Carbon $ends_on
 * @property Carbon $expires_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'profile_id',
    'token',
    'scope',
    'starts_on',
    'ends_on',
    'expires_at',
])]
class ShareLink extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'scope' => ShareLinkScope::class,
            'starts_on' => 'date',
            'ends_on' => 'date',
            'expires_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Profile, $this>
     */
    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }

    /**
     * @return HasOne<ClinicianTarget, $this>
     */
    public function clinicianTarget(): HasOne
    {
        return $this->hasOne(ClinicianTarget::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at->lessThanOrEqualTo(now());
    }

    public function canReceiveResponse(): bool
    {
        return $this->scope === ShareLinkScope::Respond && ! $this->isExpired();
    }

    public static function makeToken(): string
    {
        return Str::lower(Str::random(48));
    }
}
