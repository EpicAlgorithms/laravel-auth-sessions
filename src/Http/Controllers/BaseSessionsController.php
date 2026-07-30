<?php

declare(strict_types=1);

namespace EpicAlgorithms\AuthSessions\Http\Controllers;

use EpicAlgorithms\AuthSessions\Constants\SessionKey;
use EpicAlgorithms\AuthSessions\Enums\SessionRevokeReason;
use EpicAlgorithms\AuthSessions\Models\AuthSession;
use EpicAlgorithms\AuthSessions\Services\AuthSessionService;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View as ViewFactory;

abstract class BaseSessionsController extends Controller
{
    public function __construct(
        protected readonly AuthSessionService $authSessionService,
    ) {}

    public function index(): View
    {
        // The view() helper widens to the view Factory in its no-argument
        // form; going through the facade keeps the concrete View type.
        return ViewFactory::make((string) config('auth-sessions.views.sessions'), [
            'sessions' => $this->authSessionService->getActiveSessions($this->currentUser()),
            'currentSessionId' => session()->getId(),
        ]);
    }

    public function destroy(Request $request, AuthSession $authSession): RedirectResponse
    {
        // {authSession} is resolved through owner-scoped implicit route-model
        // binding (see AuthSessionsServiceProvider). By the time we reach here
        // the row is guaranteed to belong to the authenticated user; an unknown
        // or foreign id has already produced a 404. We only need to report
        // honestly whether a row was actually revoked.
        $revoked = $this->authSessionService->revokeSession(
            $authSession,
            SessionRevokeReason::USER_REVOKED_DEVICE,
        );

        if (! $revoked) {
            return back()->with('error', 'Session could not be terminated.');
        }

        return back()->with('status', 'Session has been terminated.');
    }

    public function destroyOthers(Request $request): RedirectResponse
    {
        $user = $this->currentUser();

        // Use auth_session_id from session data (more reliable than laravel_session_id
        // which can change during session regeneration)
        $authSessionId = session(SessionKey::AUTH_SESSION_ID);

        if (is_string($authSessionId) || is_int($authSessionId)) {
            $this->authSessionService->revokeOtherSessionsById($user, (string) $authSessionId);
        }

        return back()->with('status', 'All other sessions have been terminated.');
    }

    /**
     * The authenticated user, or a hard failure.
     *
     * These routes always sit behind the auth middleware, so a null user means
     * the middleware was misconfigured; a 401 beats passing null to the
     * session service.
     *
     * @throws AuthenticationException
     */
    protected function currentUser(): Authenticatable
    {
        $user = Auth::user();

        if ($user === null) {
            throw new AuthenticationException();
        }

        return $user;
    }
}
