<?php

declare(strict_types=1);

namespace EpicAlgorithms\AuthSessions\Http\Controllers;

use EpicAlgorithms\AuthSessions\Constants\SessionKey;
use EpicAlgorithms\AuthSessions\Enums\SessionRevokeReason;
use EpicAlgorithms\AuthSessions\Services\AuthSessionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

abstract class BaseSessionsController extends Controller
{
    public function __construct(
        protected readonly AuthSessionService $authSessionService,
    ) {}

    public function index(): View
    {
        $user = Auth::user();

        return view(config('auth-sessions.views.sessions'), [
            'sessions' => $this->authSessionService->getActiveSessions($user),
            'currentSessionId' => session()->getId(),
        ]);
    }

    public function destroy(Request $request, string $session): RedirectResponse
    {
        $user = Auth::user();

        $this->authSessionService->revokeSessionById(
            $user,
            $session,
            SessionRevokeReason::USER_REVOKED_DEVICE,
        );

        return back()->with('status', 'Session has been terminated.');
    }

    public function destroyOthers(Request $request): RedirectResponse
    {
        $user = Auth::user();

        // Use auth_session_id from session data (more reliable than laravel_session_id
        // which can change during session regeneration)
        $authSessionId = session(SessionKey::AUTH_SESSION_ID);

        if ($authSessionId) {
            $this->authSessionService->revokeOtherSessionsById($user, $authSessionId);
        }

        return back()->with('status', 'All other sessions have been terminated.');
    }
}
