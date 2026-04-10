<?php

declare(strict_types=1);

namespace EpicAlgorithms\AuthSessions\Enums;

use EpicAlgorithms\EnumModel\EnumModel;

class SessionRevokeReason extends EnumModel
{
    protected $table = 'session_revoke_reasons';

    public const int USER_LOGOUT = 1;
    public const int USER_REVOKED_DEVICE = 2;
    public const int ADMIN_REVOKED = 3;
    public const int INACTIVITY_EXPIRY = 4;
    public const int ABSOLUTE_EXPIRY = 5;
    public const int PASSWORD_CHANGED = 6;
    public const int LOGOUT_OTHER_DEVICES = 7;

    protected function getRows(): array
    {
        return [
            ['id' => self::USER_LOGOUT, 'name' => 'User Logout', 'slug' => 'user-logout'],
            ['id' => self::USER_REVOKED_DEVICE, 'name' => 'User Revoked Device', 'slug' => 'user-revoked-device'],
            ['id' => self::ADMIN_REVOKED, 'name' => 'Admin Revoked', 'slug' => 'admin-revoked'],
            ['id' => self::INACTIVITY_EXPIRY, 'name' => 'Inactivity Expiry', 'slug' => 'inactivity-expiry'],
            ['id' => self::ABSOLUTE_EXPIRY, 'name' => 'Absolute Expiry', 'slug' => 'absolute-expiry'],
            ['id' => self::PASSWORD_CHANGED, 'name' => 'Password Changed', 'slug' => 'password-changed'],
            ['id' => self::LOGOUT_OTHER_DEVICES, 'name' => 'Logout Other Devices', 'slug' => 'logout-other-devices'],
        ];
    }
}
