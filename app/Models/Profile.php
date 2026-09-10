<?php

namespace App\Models;

use App\Enums\GlucoseUnit;
use App\Enums\ProfileRole;
use App\Enums\TargetSetBy;
use App\Enums\WeightUnit;
use App\Support\SystemTimezone;
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
 * @property string|null $timezone
 * @property GlucoseUnit $glucose_unit
 * @property WeightUnit $weight_unit
 * @property string|null $glucose_low_mg_dl
 * @property string|null $glucose_high_mg_dl
 * @property TargetSetBy $glucose_target_set_by
 * @property bool $reminders_enabled
 * @property string $reminder_morning
 * @property string $reminder_afternoon
 * @property string $reminder_evening
 * @property bool $is_default
 * @property int $alert_inactivity_days
 * @property int $alert_streak
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
    'glucose_target_set_by',
    'reminders_enabled',
    'reminder_morning',
    'reminder_afternoon',
    'reminder_evening',
    'is_default',
    'alert_inactivity_days',
    'alert_streak',
])]
class Profile extends Model
{
    /** @use HasFactory<ProfileFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::created(function (Profile $profile): void {
            $user = $profile->user;

            if ($user === null) {
                return;
            }

            ProfileMember::query()->firstOrCreate(
                [
                    'profile_id' => $profile->id,
                    'user_id' => $user->id,
                ],
                [
                    'email' => mb_strtolower($user->email),
                    'role' => ProfileRole::Owner,
                    'invited_at' => now(),
                    'accepted_at' => now(),
                ],
            );
        });
    }

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
            'glucose_target_set_by' => TargetSetBy::class,
            'reminders_enabled' => 'boolean',
            'is_default' => 'boolean',
            'alert_inactivity_days' => 'integer',
            'alert_streak' => 'integer',
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
     * @return HasMany<ClinicianTarget, $this>
     */
    public function clinicianTargets(): HasMany
    {
        return $this->hasMany(ClinicianTarget::class);
    }

    /**
     * @return HasMany<ProfileMember, $this>
     */
    public function members(): HasMany
    {
        return $this->hasMany(ProfileMember::class);
    }

    /**
     * @return HasMany<PushSubscription, $this>
     */
    public function pushSubscriptions(): HasMany
    {
        return $this->hasMany(PushSubscription::class);
    }

    /**
     * @return HasMany<FoodLog, $this>
     */
    public function foodLogs(): HasMany
    {
        return $this->hasMany(FoodLog::class);
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

    public function clockTimezone(): string
    {
        $detected = SystemTimezone::detected();

        if (is_string($detected) && $detected !== '') {
            return $detected;
        }

        if (is_string($this->timezone) && $this->timezone !== '') {
            return $this->timezone;
        }

        return (string) config('app.timezone');
    }

    public function nowInTimezone(): CarbonInterface
    {
        return now($this->clockTimezone());
    }
}
