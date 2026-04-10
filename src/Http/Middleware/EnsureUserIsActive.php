<?php

declare(strict_types=1);

namespace EpicAlgorithms\AuthSessions\Http\Middleware;

use Closure;
use EpicAlgorithms\AuthSessions\Http\Middleware\Concerns\ForcesLogout;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    use ForcesLogout;

    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check() && ! Auth::user()->is_active) {
            return $this->forceLogout($request, 'Your account has been deactivated.', 'deactivated');
        }

        return $next($request);
    }
}
