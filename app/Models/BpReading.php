<?php

namespace App\Models;

use App\Enums\Arm;
use App\Enums\BodyPosition;
use App\Enums\ReadingSource;
use App\Enums\ReadingTag;
use App\Models\Concerns\ForPeriod;
use Database\Factories\BpReadingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $profile_id
 * @property int $systolic
 * @property int $diastolic
 * @property int|null $pulse
 * @property Carbon $measured_at
 * @property Arm $arm
 * @property BodyPosition $body_position
 * @property ReadingTag $tag
 * @property string|null $note
 * @property ReadingSource $source
 * @property string|null $client_uuid
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'profile_id',
    'systolic',
    'diastolic',
    'pulse',
    'measured_at',
    'arm',
    'body_position',
    'tag',
    'note',
    'source',
    'client_uuid',
])]
class BpReading extends Model
{
    /** @use HasFactory<BpReadingFactory> */
    use ForPeriod, HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'systolic' => 'integer',
            'diastolic' => 'integer',
            'pulse' => 'integer',
            'measured_at' => 'datetime',
            'arm' => Arm::class,
            'body_position' => BodyPosition::class,
            'tag' => ReadingTag::class,
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
}
