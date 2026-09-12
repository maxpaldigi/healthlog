<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateLocaleRequest;
use App\Support\AppLocale;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class LocaleController extends Controller
{
    public function update(UpdateLocaleRequest $request): RedirectResponse
    {
        $locale = (string) $request->validated('locale');

        $request->user()->forceFill(['locale' => $locale])->save();
        app()->setLocale($locale);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => AppLocale::get('appearance.language_saved'),
        ]);

        return back();
    }
}
