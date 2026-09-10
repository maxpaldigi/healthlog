<?php

namespace App\Http\Controllers;

use App\Actions\Auth\RecordConsent;
use App\Actions\Share\SaveClinicianResponse;
use App\Enums\ConsentType;
use App\Enums\ShareLinkScope;
use App\Models\ClinicianTarget;
use App\Models\ShareLink;
use App\Models\User;
use App\Support\CurrentProfile;
use App\Support\FhirExport;
use App\Support\Glucose;
use App\Support\HistoryDateRange;
use App\Support\VisitReport;
use App\Support\VisitReportPdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
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
            'scope' => ['nullable', Rule::enum(ShareLinkScope::class)],
        ]);

        $scope = ShareLinkScope::tryFrom((string) ($validated['scope'] ?? '')) ?? ShareLinkScope::Read;

        $range = HistoryDateRange::make(
            $profile,
            $validated['from'] ?? null,
            $validated['to'] ?? null,
        );

        $link = $profile->shareLinks()->create([
            'token' => ShareLink::makeToken(),
            'scope' => $scope,
            'starts_on' => $range['filters']['from'],
            'ends_on' => $range['filters']['to'],
            'expires_at' => now()->addDays((int) ($validated['days'] ?? 7)),
        ]);

        $recordConsent->handle($user, ConsentType::ShareLink);

        $days = (int) ($validated['days'] ?? 7);
        $message = $scope === ShareLinkScope::Respond
            ? 'Clinic reply link ready. They can leave a note until it expires in '.$days.' days.'
            : 'Share link ready. It expires in '.$days.' days.';

        Inertia::flash('toast', ['type' => 'success', 'message' => $message]);

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
        $existing = $link->clinicianTarget;

        return Inertia::render('shared/Report', [
            'report' => $this->reportFor($link),
            'filters' => $range['filters'],
            'presets' => $range['presets'],
            'month' => $range['month'],
            'shared' => true,
            'share_token' => $link->token,
            'expires_at' => $link->expires_at->timezone($profile->timezone)->format('j M Y, g:ia'),
            'can_respond' => $link->canReceiveResponse(),
            'respond' => $this->respondForm($link, $existing),
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

    public function fhir(string $token): HttpResponse
    {
        $link = ShareLink::query()->where('token', $token)->firstOrFail();

        abort_if($link->isExpired(), 410, 'This share link has expired.');

        $profile = $link->profile;
        $range = HistoryDateRange::make(
            $profile,
            $link->starts_on->toDateString(),
            $link->ends_on->toDateString(),
        );

        $bundle = FhirExport::make(
            $profile,
            $range['filters']['from'],
            $range['filters']['to'],
            $profile->bpReadings()->forPeriod($range['from'], $range['to'])->orderBy('measured_at')->get(),
            $profile->glucoseReadings()->forPeriod($range['from'], $range['to'])->orderBy('measured_at')->get(),
            $profile->hba1cResults()->forPeriod($range['from'], $range['to'])->orderBy('tested_at')->get(),
            $profile->medications()->orderByDesc('is_active')->orderBy('name')->get(),
        );

        return FhirExport::download($bundle, $range['filters']['from'], $range['filters']['to']);
    }

    public function respond(Request $request, string $token, SaveClinicianResponse $save): RedirectResponse
    {
        $link = ShareLink::query()->where('token', $token)->firstOrFail();

        abort_if($link->isExpired(), 410, 'This share link has expired.');
        abort_unless($link->canReceiveResponse(), 403);

        $validated = $request->validate([
            'acknowledged' => ['accepted'],
            'instruction' => ['nullable', 'string', 'max:500'],
            'glucose_low' => ['nullable', 'numeric'],
            'glucose_high' => ['nullable', 'numeric'],
        ]);

        $save->handle($link, $validated, $request->ip());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Note saved on this log.']);

        return back();
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

    /**
     * @return array{glucose_unit: string, glucose_low: string, glucose_high: string, instruction: string}|null
     */
    private function respondForm(ShareLink $link, ?ClinicianTarget $existing): ?array
    {
        if (! $link->canReceiveResponse()) {
            return null;
        }

        $profile = $link->profile;
        $storedLow = $existing !== null && $existing->glucose_low_mg_dl !== null
            ? (string) $existing->glucose_low_mg_dl
            : ($profile->glucose_low_mg_dl === null ? null : (string) $profile->glucose_low_mg_dl);
        $storedHigh = $existing !== null && $existing->glucose_high_mg_dl !== null
            ? (string) $existing->glucose_high_mg_dl
            : ($profile->glucose_high_mg_dl === null ? null : (string) $profile->glucose_high_mg_dl);

        return [
            'glucose_unit' => $profile->glucose_unit->label(),
            'glucose_low' => $storedLow === null ? '' : Glucose::fromMgDl($storedLow, $profile->glucose_unit),
            'glucose_high' => $storedHigh === null ? '' : Glucose::fromMgDl($storedHigh, $profile->glucose_unit),
            'instruction' => $existing === null || $existing->instruction === null ? '' : $existing->instruction,
        ];
    }
}
