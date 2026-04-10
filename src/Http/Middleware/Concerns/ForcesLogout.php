<?php

declare(strict_types=1);

namespace EpicAlgorithms\AuthSessions\Http\Middleware\Concerns;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

trait ForcesLogout
{
    protected function forceLogout(Request $request, string $errorMessage, string $errorKey = 'email'): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->withErrors([$errorKey => $errorMessage]);
    }
}
