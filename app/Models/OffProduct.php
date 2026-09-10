<?php

namespace App\Models;

use Database\Factories\OffProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Cached Open Food Facts row. Kept separate from food_logs so ODbL data is
 * never merged into another source table.
 *
 * @property int $id
 * @property string $barcode
 * @property string $name
 * @property string|null $brands
 * @property string|null $sodium_mg_100g
 * @property string|null $sugars_g_100g
 * @property string|null $carbs_g_100g
 * @property string|null $nutriscore
 * @property string|null $serving_g
 * @property Carbon $fetched_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'barcode',
    'name',
    'brands',
    'sodium_mg_100g',
    'sugars_g_100g',
    'carbs_g_100g',
    'nutriscore',
    'serving_g',
    'fetched_at',
])]
class OffProduct extends Model
{
    /** @use HasFactory<OffProductFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sodium_mg_100g' => 'decimal:2',
            'sugars_g_100g' => 'decimal:2',
            'carbs_g_100g' => 'decimal:2',
            'serving_g' => 'decimal:2',
            'fetched_at' => 'datetime',
        ];
    }
}
