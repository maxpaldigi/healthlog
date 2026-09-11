<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\ListAccounts;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request, ListAccounts $listAccounts): Response
    {
        $this->authorize('viewAny', User::class);

        return Inertia::render('admin/Dashboard', $listAccounts->handle());
    }
}
