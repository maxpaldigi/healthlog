<?php

namespace App\Http\Controllers;

use App\Actions\Readings\ProcessScanPhoto;
use App\Http\Requests\Readings\StoreScanPhotoRequest;
use App\Support\CurrentProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ScanController extends Controller
{
    public function create(Request $request): Response
    {
        $expect = $request->validate([
            'expect' => ['nullable', 'in:bp,glucose'],
        ])['expect'] ?? null;

        return Inertia::render('readings/Scan', [
            'status' => $request->session()->get('scan_status'),
            'expect' => $expect,
        ]);
    }

    public function store(
        StoreScanPhotoRequest $request,
        CurrentProfile $currentProfile,
        ProcessScanPhoto $processScanPhoto,
    ): RedirectResponse {
        $profile = $currentProfile->handle($request->user(), $request);
        $photo = $request->file('photo');

        abort_unless($photo !== null, 422);

        return $processScanPhoto->handle($photo, $profile, $request->validated('expect'));
    }
}
