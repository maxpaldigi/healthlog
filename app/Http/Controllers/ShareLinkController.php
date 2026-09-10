<?php

namespace App\Http\Controllers;

use App\Actions\Auth\RecordConsent;
use App\Enums\ConsentType;
use App\Models\ShareLink;
use App\Models\User;
use App\Support\CurrentProfile;
use App\Support\HistoryDateRange;
use App\Support\VisitReport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class ShareLinkController extends Controller
{
    public function store(Request $request, CurrentProfile $currentProfile, RecordConsent $recordConsent): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        $profile = $currentProfile->handle($user, $request);

        $this->authorize('create', [ShareLink::class, $profile]);

        $validated = $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d'],
            'days' => ['nullable', 'integer', 'between:1,30'],
        ]);

        $range = HistoryDateRange::make(
            $profile,
            $validated['from'] ?? null,
            $validated['to'] ?? null,
        );

        $link = $profile->shareLinks()->create([
            'token' => ShareLink::makeToken(),
            'starts_on' => $range['filters']['from'],
            'ends_on' => $range['filters']['to'],
            'expires_at' => now()->addDays((int) ($validated['days'] ?? 7)),
        ]);

        $recordConsent->handle($user, ConsentType::ShareLink);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Share link ready. It expires in '.($validated['days'] ?? 7).' days.']);

        return back()->with('share_url', route('shared.show', $link->token));
    }

    public function show(string $token): Response
    {
        $link = ShareLink::query()->where('token', $token)->firstOrFail();

        abort_if($link->isExpired(), 410, 'This share link has expired.');

        $profile = $link->profile;
        $range = HistoryDateRange::make(
            $profile,
            $link->starts_on->toDateString(),
            $link->ends_on->toDateString(),
        );

        $fromLocal = Carbon::parse($link->starts_on->toDateString(), $profile->timezone)->startOfDay();
        $toLocal = Carbon::parse($link->ends_on->toDateString(), $profile->timezone)->startOfDay();

        $bp = $profile->bpReadings()->forPeriod($range['from'], $range['to'])->orderBy('measured_at')->get();
        $glucose = $profile->glucoseReadings()->forPeriod($range['from'], $range['to'])->orderBy('measured_at')->get();
        $weights = $profile->weights()->forPeriod($range['from'], $range['to'])->orderByDesc('measured_at')->get();
        $hba1c = $profile->hba1cResults()->forPeriod($range['from'], $range['to'])->orderByDesc('tested_at')->get();
        $medications = $profile->medications()->orderByDesc('is_active')->orderBy('name')->get();

        return Inertia::render('shared/Report', [
            'report' => VisitReport::make(
                $profile,
                $fromLocal,
                $toLocal,
                $bp,
                $glucose,
                $weights,
                $hba1c,
                $medications,
            ),
            'filters' => $range['filters'],
            'presets' => $range['presets'],
            'month' => $range['month'],
            'shared' => true,
            'expires_at' => $link->expires_at->timezone($profile->timezone)->format('j M Y, g:ia'),
        ]);
    }
}
