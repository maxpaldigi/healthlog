<?php

namespace App\Models;

use App\Enums\GlucoseUnit;
use App\Enums\WeightUnit;
use Carbon\CarbonInterface;
use Database\Factories\ProfileFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property Carbon|null $date_of_birth
 * @property string $timezone
 * @property GlucoseUnit $glucose_unit
 * @property WeightUnit $weight_unit
 * @property string|null $glucose_low_mg_dl
 * @property string|null $glucose_high_mg_dl
 * @property bool $reminders_enabled
 * @property string $reminder_morning
 * @property string $reminder_afternoon
 * @property string $reminder_evening
 * @property bool $is_default
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'user_id',
    'name',
    'date_of_birth',
    'timezone',
    'glucose_unit',
    'weight_unit',
    'glucose_low_mg_dl',
    'glucose_high_mg_dl',
    'reminders_enabled',
    'reminder_morning',
    'reminder_afternoon',
    'reminder_evening',
    'is_default',
])]
class Profile extends Model
{
    /** @use HasFactory<ProfileFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'glucose_unit' => GlucoseUnit::class,
            'weight_unit' => WeightUnit::class,
            'glucose_low_mg_dl' => 'decimal:2',
            'glucose_high_mg_dl' => 'decimal:2',
            'reminders_enabled' => 'boolean',
            'is_default' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<BpReading, $this>
     */
    public function bpReadings(): HasMany
    {
        return $this->hasMany(BpReading::class);
    }

    /**
     * @return HasMany<GlucoseReading, $this>
     */
    public function glucoseReadings(): HasMany
    {
        return $this->hasMany(GlucoseReading::class);
    }

    /**
     * @return HasMany<Hba1cResult, $this>
     */
    public function hba1cResults(): HasMany
    {
        return $this->hasMany(Hba1cResult::class);
    }

    /**
     * @return HasMany<Medication, $this>
     */
    public function medications(): HasMany
    {
        return $this->hasMany(Medication::class);
    }

    /**
     * @return HasMany<Weight, $this>
     */
    public function weights(): HasMany
    {
        return $this->hasMany(Weight::class);
    }

    /**
     * @return HasMany<ShareLink, $this>
     */
    public function shareLinks(): HasMany
    {
        return $this->hasMany(ShareLink::class);
    }

    /**
     * @return array<string, string>
     */
    public function reminderTimes(): array
    {
        return [
            'morning' => $this->reminder_morning ?: '08:00',
            'afternoon' => $this->reminder_afternoon ?: '14:00',
            'evening' => $this->reminder_evening ?: '20:00',
        ];
    }

    public function nowInTimezone(): CarbonInterface
    {
        return now($this->timezone);
    }
}
