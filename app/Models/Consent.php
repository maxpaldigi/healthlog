<?php

namespace App\Models;

use App\Enums\ConsentType;
use Database\Factories\ConsentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property ConsentType $type
 * @property Carbon $granted_at
 * @property Carbon|null $revoked_at
 * @property string|null $ip_address
 * @property string $policy_version
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'user_id',
    'type',
    'granted_at',
    'revoked_at',
    'ip_address',
    'policy_version',
])]
class Consent extends Model
{
    public const POLICY_VERSION = '2026-09-10';

    /** @use HasFactory<ConsentFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => ConsentType::class,
            'granted_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isActive(): bool
    {
        return $this->revoked_at === null;
    }
}
