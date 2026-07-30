<?php

declare(strict_types=1);

namespace EpicAlgorithms\AuthSessions\Http\Controllers\Api;

use EpicAlgorithms\ApiKit\Http\Concerns\HandlesApiQuery;
use EpicAlgorithms\ApiKit\Http\Controllers\ApiController;
use EpicAlgorithms\AuthSessions\Http\Concerns\ResolvesAuthenticatedUser;
use EpicAlgorithms\AuthSessions\Constants\SessionKey;
use EpicAlgorithms\AuthSessions\Enums\SessionRevokeReason;
use EpicAlgorithms\AuthSessions\Http\Resources\AuthSessionResource;
use EpicAlgorithms\AuthSessions\Models\AuthSession;
use EpicAlgorithms\AuthSessions\Services\AuthSessionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * JSON REST API for the authenticated user's own auth sessions.
 *
 * Every action is owner-scoped: the collection is filtered to the auth user's
 * rows, and the `{authSession}` route parameter is resolved through an
 * owner-scoped route binding (see AuthSessionsServiceProvider), so a session
 * belonging to another user - or an unknown id - yields a 404 before the action
 * body runs. This mirrors the web BaseSessionsController but speaks the api-kit
 * v2 envelope.
 */
class SessionApiController extends ApiController
{
    use ResolvesAuthenticatedUser;

    use HandlesApiQuery;

    public function __construct(
        private readonly AuthSessionService $authSessionService,
    ) {}

    /**
     * GET /api/account/sessions - the auth user's active sessions.
     *
     * Sortable by `last_seen_at` / `created_at`, filterable by `device_type_id`.
     * Defaults to most-recently-seen first when no sort is given.
     */
    public function index(Request $request): JsonResponse
    {
        $query = AuthSession::query()
            ->where('user_id', $this->authenticated($request)->getAuthIdentifier())
            ->whereNull('revoked_at')
            ->where('expires_at', '>', now());

        $query = $this->applyApiFilters($query, $request, ['device_type_id']);
        $query = $this->applyApiSorts($query, $request, ['last_seen_at', 'created_at']);

        if ($request->query('sort') === null) {
            $query->orderByDesc('last_seen_at');
        }

        return $this->respondPaginated(
            $this->apiPaginate($query, $request),
            AuthSessionResource::class,
        );
    }

    /**
     * GET /api/account/sessions/{authSession} - show one owned session.
     */
    public function show(AuthSession $authSession): JsonResponse
    {
        return $this->respond(AuthSessionResource::make($authSession));
    }

    /**
     * DELETE /api/account/sessions/{authSession} - revoke one owned session.
     */
    public function destroy(AuthSession $authSession): JsonResponse
    {
        $revoked = $this->authSessionService->revokeSession(
            $authSession,
            SessionRevokeReason::USER_REVOKED_DEVICE,
        );

        if (! $revoked) {
            return $this->fail('Session could not be revoked.', 409, 'SESSION_NOT_REVOKED');
        }

        return $this->respondNoContent();
    }

    /**
     * DELETE /api/account/sessions - revoke every session except the current one.
     *
     * The current session is resolved from the runtime session
     * (`SessionKey::AUTH_SESSION_ID`, set by the package middleware), falling
     * back to a client-supplied `current_session_id` for stateless callers that
     * know their own session id. When neither is available there is nothing to
     * preserve, so nothing is revoked rather than risk logging the caller out.
     */
    public function destroyOthers(Request $request): JsonResponse
    {
        $user = $this->authenticated($request);

        $currentSessionId = session(SessionKey::AUTH_SESSION_ID)
            ?? $request->input('current_session_id');

        $revoked = $currentSessionId !== null
            ? $this->authSessionService->revokeOtherSessionsById($user, (string) $currentSessionId)
            : 0;

        return $this->respond(['revoked' => $revoked]);
    }
}
