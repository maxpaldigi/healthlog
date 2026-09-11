<?php

namespace Database\Seeders;

use App\Actions\Auth\EnsureSuperAdmin;
use App\Enums\Arm;
use App\Enums\BodyPosition;
use App\Enums\GlucoseContext;
use App\Enums\GlucoseUnit;
use App\Enums\ReadingSource;
use App\Enums\ReadingTag;
use App\Enums\WeightUnit;
use App\Models\BpReading;
use App\Models\GlucoseReading;
use App\Models\Profile;
use App\Models\User;
use App\Support\DemoAccount;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        app(EnsureSuperAdmin::class)->handle();

        if (app()->isProduction()) {
            return;
        }

        $user = User::query()->firstOrCreate(
            ['email' => DemoAccount::EMAIL],
            [
                'name' => 'Priya Sharma',
                'password' => null,
                'email_verified_at' => now(),
            ],
        );

        $profile = $user->defaultProfile ?? Profile::query()->create([
            'user_id' => $user->id,
            'name' => $user->name,
            'date_of_birth' => '1974-03-12',
            'timezone' => 'Asia/Kolkata',
            'glucose_unit' => GlucoseUnit::MgDl,
            'weight_unit' => WeightUnit::Kg,
            'is_default' => true,
        ]);

        if (BpReading::query()->where('profile_id', $profile->id)->doesntExist()) {
            $this->seedReadings($profile);
        }
    }

    private function seedReadings(Profile $profile): void
    {
        $timezone = $profile->timezone;
        $end = Carbon::now($timezone)->subDay()->startOfDay();

        for ($day = 29; $day >= 0; $day--) {
            $date = $end->copy()->subDays($day);
            $variation = (int) round(sin($day / 3) * 8);

            BpReading::create([
                'profile_id' => $profile->id,
                'systolic' => 126 + $variation,
                'diastolic' => 78 + (int) round($variation / 2),
                'pulse' => 68 + ($day % 5),
                'measured_at' => $date->copy()->setTime(7, 30)->utc(),
                'arm' => Arm::Left,
                'body_position' => BodyPosition::Sitting,
                'tag' => ReadingTag::BeforeMeds,
                'note' => null,
                'source' => ReadingSource::Manual,
            ]);

            BpReading::create([
                'profile_id' => $profile->id,
                'systolic' => 132 + $variation,
                'diastolic' => 82 + (int) round($variation / 2),
                'pulse' => 72 + ($day % 4),
                'measured_at' => $date->copy()->setTime(20, 0)->utc(),
                'arm' => Arm::Left,
                'body_position' => BodyPosition::Sitting,
                'tag' => ReadingTag::None,
                'note' => null,
                'source' => ReadingSource::Manual,
            ]);

            GlucoseReading::create([
                'profile_id' => $profile->id,
                'value_mg_dl' => number_format(108 + $variation, 2, '.', ''),
                'context' => GlucoseContext::Fasting,
                'measured_at' => $date->copy()->setTime(7, 0)->utc(),
                'note' => null,
                'source' => ReadingSource::Manual,
            ]);

            if ($day % 2 === 0) {
                GlucoseReading::create([
                    'profile_id' => $profile->id,
                    'value_mg_dl' => number_format(156 + $variation, 2, '.', ''),
                    'context' => GlucoseContext::PostMeal2h,
                    'measured_at' => $date->copy()->setTime(14, 0)->utc(),
                    'note' => null,
                    'source' => ReadingSource::Manual,
                ]);
            }
        }
    }
}
