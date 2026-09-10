<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $profile_id
 * @property int $share_link_id
 * @property string|null $instruction
 * @property string|null $glucose_low_mg_dl
 * @property string|null $glucose_high_mg_dl
 * @property Carbon|null $acknowledged_at
 * @property string|null $ip_address
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'profile_id',
    'share_link_id',
    'instruction',
    'glucose_low_mg_dl',
    'glucose_high_mg_dl',
    'acknowledged_at',
    'ip_address',
])]
class ClinicianTarget extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'glucose_low_mg_dl' => 'decimal:2',
            'glucose_high_mg_dl' => 'decimal:2',
            'acknowledged_at' => 'datetime',
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
     * @return BelongsTo<ShareLink, $this>
     */
    public function shareLink(): BelongsTo
    {
        return $this->belongsTo(ShareLink::class);
    }
}
