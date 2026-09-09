<?php

namespace App\Models;

use App\Models\Concerns\ForPeriod;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $profile_id
 * @property string $kg
 * @property Carbon $measured_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'profile_id',
    'kg',
    'measured_at',
])]
class Weight extends Model
{
    use ForPeriod;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'kg' => 'decimal:2',
            'measured_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Profile, $this>
     */
    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }
}
