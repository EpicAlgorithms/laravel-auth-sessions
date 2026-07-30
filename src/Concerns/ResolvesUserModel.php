<?php

declare(strict_types=1);

namespace EpicAlgorithms\AuthSessions\Concerns;

use Illuminate\Database\Eloquent\Model;
use RuntimeException;

/**
 * Resolves the host application's user model from configuration.
 *
 * The class name is host-supplied, so it is validated here rather than being
 * trusted blindly: a typo in `auth-sessions.user_model` otherwise surfaces as
 * an opaque failure deep inside Eloquent's relation resolution.
 */
trait ResolvesUserModel
{
    /**
     * @return class-string<Model>
     */
    protected function userModel(): string
    {
        $userModel = (string) config('auth-sessions.user_model', 'App\Models\User');

        if (! is_subclass_of($userModel, Model::class)) {
            throw new RuntimeException(
                "The configured auth-sessions user model [{$userModel}] must be an Eloquent model."
            );
        }

        return $userModel;
    }
}
