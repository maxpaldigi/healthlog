<?php

namespace App\Models;

use App\Enums\GlucoseContext;
use App\Enums\ReadingSource;
use App\Models\Concerns\ForPeriod;
use Database\Factories\GlucoseReadingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $profile_id
 * @property string $value_mg_dl
 * @property GlucoseContext $context
 * @property Carbon $measured_at
 * @property string|null $note
 * @property ReadingSource $source
 * @property string|null $client_uuid
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'profile_id',
    'value_mg_dl',
    'context',
    'measured_at',
    'note',
    'source',
    'client_uuid',
])]
class GlucoseReading extends Model
{
    /** @use HasFactory<GlucoseReadingFactory> */
    use ForPeriod, HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'value_mg_dl' => 'decimal:2',
            'context' => GlucoseContext::class,
            'measured_at' => 'datetime',
            'source' => ReadingSource::class,
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
     * @return HasMany<FoodLog, $this>
     */
    public function foodLogs(): HasMany
    {
        return $this->hasMany(FoodLog::class);
    }
}
