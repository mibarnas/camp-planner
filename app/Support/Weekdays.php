<?php

namespace App\Support;

class Weekdays
{
    /**
     * Weekday names keyed by ISO day number (1 = Monday), in the app's current
     * locale. The English names double as the translation keys, so `lang/sk.json`
     * carries the Slovak forms alongside every other translated string.
     *
     * @var array<int, string>
     */
    private const KEYS = [
        1 => 'weekday.monday',
        2 => 'weekday.tuesday',
        3 => 'weekday.wednesday',
        4 => 'weekday.thursday',
        5 => 'weekday.friday',
        6 => 'weekday.saturday',
        7 => 'weekday.sunday',
    ];

    /**
     * The localised weekday name for an ISO day number (1 = Monday).
     */
    public static function for(int $isoDayOfWeek): string
    {
        $key = self::KEYS[$isoDayOfWeek] ?? null;

        return $key === null ? '' : __($key);
    }
}
