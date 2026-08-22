<?php

namespace App\Support;

class Weekdays
{
    /** @var array<int, string> */
    private const SK = [
        1 => 'pondelok',
        2 => 'utorok',
        3 => 'streda',
        4 => 'štvrtok',
        5 => 'piatok',
        6 => 'sobota',
        7 => 'nedeľa',
    ];

    /**
     * The Slovak weekday name for an ISO day number (1 = Monday).
     */
    public static function sk(int $isoDayOfWeek): string
    {
        return self::SK[$isoDayOfWeek] ?? '';
    }
}
