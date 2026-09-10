<?php

namespace App\Models;

use App\Enums\ProfileRole;
use Database\Factories\ProfileMemberFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $profile_id
 * @property int|null $user_id
 * @property string $email
 * @property ProfileRole $role
 * @property Carbon $invited_at
 * @property Carbon|null $accepted_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'profile_id',
    'user_id',
    'email',
    'role',
    'invited_at',
    'accepted_at',
])]
class ProfileMember extends Model
{
    /** @use HasFactory<ProfileMemberFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'role' => ProfileRole::class,
            'invited_at' => 'datetime',
            'accepted_at' => 'datetime',
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
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isAccepted(): bool
    {
        return $this->accepted_at !== null;
    }

    /**
     * @param  Builder<ProfileMember>  $query
     * @return Builder<ProfileMember>
     */
    public function scopeAccepted(Builder $query): Builder
    {
        return $query->whereNotNull('accepted_at');
    }

    /**
     * @param  Builder<ProfileMember>  $query
     * @return Builder<ProfileMember>
     */
    public function scopeCaregivers(Builder $query): Builder
    {
        return $query->where('role', ProfileRole::Caregiver);
    }
}
