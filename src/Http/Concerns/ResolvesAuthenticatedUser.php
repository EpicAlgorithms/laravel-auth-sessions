<?php

declare(strict_types=1);

namespace EpicAlgorithms\AuthSessions\Http\Concerns;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;

/**
 * Resolves the authenticated user, or fails loudly.
 *
 * Every route that reaches these controllers runs behind the module auth
 * middleware, so a null user means the middleware was misconfigured; surfacing
 * that as a 401 beats passing null down into the session service.
 */
trait ResolvesAuthenticatedUser
{
    /**
     * @throws AuthenticationException
     */
    protected function authenticated(Request $request): Authenticatable
    {
        $user = $request->user();

        if ($user === null) {
            throw new AuthenticationException();
        }

        return $user;
    }
}
