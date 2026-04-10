<?php

declare(strict_types=1);

namespace EpicAlgorithms\AuthSessions\Constants;

final class SessionKey
{
    public const TFA_USER_ID = 'auth.2fa_user_id';

    public const REMEMBER_ME = 'auth.remember_me';

    public const PENDING_LOGIN_METHOD = 'auth.pending_login_method';

    public const LOGIN_METHOD = 'auth.login_method';

    public const SKIP_NEW_DEVICE_EMAIL = 'auth.skip_new_device_email';

    public const AUTH_SESSION_ID = 'auth_session_id';

    public const IMPERSONATOR_ID = 'impersonator_id';

    public const TFA_SETUP_SECRET = '2fa.setup_secret';

    public const TFA_RECOVERY_CODES = '2fa.recovery_codes';
}
