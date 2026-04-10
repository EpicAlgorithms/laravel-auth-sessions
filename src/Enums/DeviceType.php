<?php

declare(strict_types=1);

namespace EpicAlgorithms\AuthSessions\Enums;

use EpicAlgorithms\EnumModel\EnumModel;

class DeviceType extends EnumModel
{
    protected $table = 'device_types';

    public const int DESKTOP = 1;
    public const int MOBILE = 2;
    public const int TABLET = 3;

    protected function getRows(): array
    {
        return [
            ['id' => self::DESKTOP, 'name' => 'Desktop', 'slug' => 'desktop'],
            ['id' => self::MOBILE, 'name' => 'Mobile', 'slug' => 'mobile'],
            ['id' => self::TABLET, 'name' => 'Tablet', 'slug' => 'tablet'],
        ];
    }
}
