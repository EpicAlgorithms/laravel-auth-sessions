<?php

declare(strict_types=1);

namespace EpicAlgorithms\AuthSessions\Enums;

use EpicAlgorithms\EnumModel\EnumModel;

class LoginMethod extends EnumModel
{
    protected $table = 'login_methods';

    public const int PASSWORD = 1;
    public const int GOOGLE = 2;
    public const int MICROSOFT = 3;
    public const int MAGIC_LINK = 4;
    public const int SSO = 5;
    public const int IMPERSONATION = 6;
    public const int REMEMBER_TOKEN = 7;

    protected function getRows(): array
    {
        return [
            ['id' => self::PASSWORD, 'name' => 'Email & Password', 'slug' => 'password'],
            ['id' => self::GOOGLE, 'name' => 'Google', 'slug' => 'google'],
            ['id' => self::MICROSOFT, 'name' => 'Microsoft', 'slug' => 'microsoft'],
            ['id' => self::MAGIC_LINK, 'name' => 'Magic Link', 'slug' => 'magic-link'],
            ['id' => self::SSO, 'name' => 'SSO', 'slug' => 'sso'],
            ['id' => self::IMPERSONATION, 'name' => 'Impersonation', 'slug' => 'impersonation'],
            ['id' => self::REMEMBER_TOKEN, 'name' => 'Remember Token', 'slug' => 'remember-token'],
        ];
    }
}
