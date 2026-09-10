<?php

namespace App\Models;

use App\Enums\FoodSource;
use Database\Factories\FoodLogFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $profile_id
 * @property FoodSource $source
 * @property string|null $source_ref
 * @property string $label
 * @property string $portion_g
 * @property string $portion_label
 * @property Carbon $eaten_at
 * @property int|null $glucose_reading_id
 * @property string|null $sodium_mg_100g
 * @property string|null $sugars_g_100g
 * @property string|null $carbs_g_100g
 * @property string|null $nutriscore
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'profile_id',
    'source',
    'source_ref',
    'label',
    'portion_g',
    'portion_label',
    'eaten_at',
    'glucose_reading_id',
    'sodium_mg_100g',
    'sugars_g_100g',
    'carbs_g_100g',
    'nutriscore',
])]
class FoodLog extends Model
{
    /** @use HasFactory<FoodLogFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'source' => FoodSource::class,
            'portion_g' => 'decimal:2',
            'eaten_at' => 'datetime',
            'sodium_mg_100g' => 'decimal:2',
            'sugars_g_100g' => 'decimal:2',
            'carbs_g_100g' => 'decimal:2',
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
     * @return BelongsTo<GlucoseReading, $this>
     */
    public function glucoseReading(): BelongsTo
    {
        return $this->belongsTo(GlucoseReading::class);
    }
}
