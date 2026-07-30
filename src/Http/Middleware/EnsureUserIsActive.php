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
        $user = Auth::user();

        // is_active lives on the host application's user model, so it is
        // read as a dynamic attribute rather than a declared property.
        if ($user !== null && ! $user->getAttribute('is_active')) {
            return $this->forceLogout($request, 'Your account has been deactivated.', 'deactivated');
        }

        return $next($request);
    }
}
