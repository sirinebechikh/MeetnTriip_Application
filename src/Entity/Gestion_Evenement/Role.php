<?php

namespace App\Enum;

class Role
{
    const ADMIN = 'ADMIN';
    const CLIENT = 'CLIENT';
    const SPONSOR = 'SPONSOR';
    const EMPLOY = 'EMPLOY';

    public static function getRoles(): array
    {
        return [
            self::ADMIN,
            self::CLIENT,
            self::SPONSOR,
            self::EMPLOY
        ];
    }
}