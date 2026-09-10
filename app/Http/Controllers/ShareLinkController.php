<?php

namespace App\Http\Controllers;

use App\Actions\Auth\RecordConsent;
use App\Enums\ConsentType;
use App\Models\ShareLink;
use App\Models\User;
use App\Support\CurrentProfile;
use App\Support\HistoryDateRange;
use App\Support\VisitReport;
use App\Support\VisitReportPdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

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

        return Inertia::render('shared/Report', [
            'report' => $this->reportFor($link),
            'filters' => $range['filters'],
            'presets' => $range['presets'],
            'month' => $range['month'],
            'shared' => true,
            'share_token' => $link->token,
            'expires_at' => $link->expires_at->timezone($profile->timezone)->format('j M Y, g:ia'),
        ]);
    }

    public function pdf(string $token): HttpResponse
    {
        $link = ShareLink::query()->where('token', $token)->firstOrFail();

        abort_if($link->isExpired(), 410, 'This share link has expired.');

        $report = $this->reportFor($link);

        return response(VisitReportPdf::render($report), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.VisitReportPdf::filename($report).'"',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function reportFor(ShareLink $link): array
    {
        $profile = $link->profile;
        $range = HistoryDateRange::make(
            $profile,
            $link->starts_on->toDateString(),
            $link->ends_on->toDateString(),
        );

        $fromLocal = Carbon::parse($link->starts_on->toDateString(), $profile->timezone)->startOfDay();
        $toLocal = Carbon::parse($link->ends_on->toDateString(), $profile->timezone)->startOfDay();

        return VisitReport::make(
            $profile,
            $fromLocal,
            $toLocal,
            $profile->bpReadings()->forPeriod($range['from'], $range['to'])->orderBy('measured_at')->get(),
            $profile->glucoseReadings()->forPeriod($range['from'], $range['to'])->orderBy('measured_at')->get(),
            $profile->weights()->forPeriod($range['from'], $range['to'])->orderByDesc('measured_at')->get(),
            $profile->hba1cResults()->forPeriod($range['from'], $range['to'])->orderByDesc('tested_at')->get(),
            $profile->medications()->orderByDesc('is_active')->orderBy('name')->get(),
        );
    }
}
