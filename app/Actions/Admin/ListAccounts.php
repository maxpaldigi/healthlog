<?php

namespace App\Actions\Admin;

use App\Enums\ConsentType;
use App\Models\User;
use DateTimeInterface;
use Illuminate\Support\Carbon;

class ListAccounts
{
    /**
     * Account directory for the operator. Counts and dates only — never reading values.
     *
     * @return array{
     *     totals: array{users: int, people: int, bp_readings: int, glucose_readings: int},
     *     users: array<int, array{
     *         id: int,
     *         name: string,
     *         email: string,
     *         joined_at: string,
     *         last_logged_at: string|null,
     *         people_count: int,
     *         bp_count: int,
     *         glucose_count: int,
     *         health_consent: bool,
     *         is_super_admin: bool
     *     }>
     * }
     */
    public function handle(): array
    {
        $users = User::query()
            ->orderByDesc('created_at')
            ->withCount('profiles')
            ->with([
                'consents' => fn ($query) => $query
                    ->where('type', ConsentType::HealthData)
                    ->whereNull('revoked_at'),
                'profiles' => fn ($query) => $query
                    ->withCount(['bpReadings', 'glucoseReadings'])
                    ->withMax('bpReadings', 'measured_at')
                    ->withMax('glucoseReadings', 'measured_at'),
            ])
            ->get();

        $rows = [];

        foreach ($users as $user) {
            $lastBp = $user->profiles->max('bp_readings_max_measured_at');
            $lastGlucose = $user->profiles->max('glucose_readings_max_measured_at');
            $lastLogged = collect([$lastBp, $lastGlucose])->filter()->max();

            $rows[] = [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'joined_at' => $user->created_at?->toIso8601String() ?? '',
                'last_logged_at' => $this->iso($lastLogged),
                'people_count' => $user->profiles_count,
                'bp_count' => (int) $user->profiles->sum('bp_readings_count'),
                'glucose_count' => (int) $user->profiles->sum('glucose_readings_count'),
                'health_consent' => $user->consents->isNotEmpty(),
                'is_super_admin' => $user->is_super_admin,
            ];
        }

        return [
            'totals' => [
                'users' => $users->count(),
                'people' => (int) $users->sum('profiles_count'),
                'bp_readings' => (int) collect($rows)->sum('bp_count'),
                'glucose_readings' => (int) collect($rows)->sum('glucose_count'),
            ],
            'users' => $rows,
        ];
    }

    private function iso(mixed $value): ?string
    {
        if ($value instanceof DateTimeInterface) {
            return $value->format(DATE_ATOM);
        }

        if (! is_string($value)) {
            return null;
        }

        return Carbon::parse($value)->toIso8601String();
    }
}
