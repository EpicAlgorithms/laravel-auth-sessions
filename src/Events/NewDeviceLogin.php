<?php

declare(strict_types=1);

namespace EpicAlgorithms\AuthSessions\Events;

use EpicAlgorithms\AuthSessions\Models\AuthSession;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewDeviceLogin
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly Authenticatable $user,
        public readonly AuthSession $session,
        public readonly string $ip,
        public readonly string $userAgent
    ) {}
}
