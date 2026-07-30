<?php

declare(strict_types=1);

namespace EpicAlgorithms\AuthSessions\Http\Controllers\Api;

use EpicAlgorithms\ApiKit\Http\Concerns\HandlesApiQuery;
use EpicAlgorithms\ApiKit\Http\Controllers\ApiController;
use EpicAlgorithms\AuthSessions\Http\Concerns\ResolvesAuthenticatedUser;
use EpicAlgorithms\AuthSessions\Http\Resources\AuthDeviceResource;
use EpicAlgorithms\AuthSessions\Models\AuthDevice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * JSON REST API for the authenticated user's own known devices.
 *
 * Owner-scoped like {@see SessionApiController}: the list is filtered to the
 * auth user's rows, and `{authDevice}` is resolved through an owner-scoped route
 * binding, so another user's device (or an unknown id) yields a 404.
 */
class DeviceApiController extends ApiController
{
    use ResolvesAuthenticatedUser;

    use HandlesApiQuery;

    /**
     * GET /api/account/devices - the auth user's known devices.
     *
     * Sortable by `created_at` / `requires_reauth_at`, defaulting to newest
     * first.
     */
    public function index(Request $request): JsonResponse
    {
        $query = AuthDevice::query()
            ->where('user_id', $this->authenticated($request)->getAuthIdentifier());

        $query = $this->applyApiSorts($query, $request, ['created_at', 'requires_reauth_at']);

        if ($request->query('sort') === null) {
            $query->orderByDesc('created_at');
        }

        return $this->respondPaginated(
            $this->apiPaginate($query, $request),
            AuthDeviceResource::class,
        );
    }

    /**
     * DELETE /api/account/devices/{authDevice} - force the device to re-auth.
     *
     * Flags the owned device so the package's EnforceDeviceReauth middleware
     * will require a fresh authentication on its next request.
     */
    public function destroy(AuthDevice $authDevice): JsonResponse
    {
        $authDevice->update(['requires_reauth_at' => now()]);

        return $this->respondNoContent();
    }
}
