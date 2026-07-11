<?php

namespace App\Support;

use Illuminate\Support\Carbon;

/**
 * Slovak name days ("meniny"), loaded from the bundled calendar CSV
 * (resources/data/sk-meniny.csv, rows of `MM-DD,name`).
 */
class NameDays
{
    /** @var array<string, string>|null map of 'MM-DD' => name */
    protected static ?array $map = null;

    /**
     * @return array<string, string>
     */
    protected static function map(): array
    {
        if (static::$map !== null) {
            return static::$map;
        }

        static::$map = [];
        $path = resource_path('data/sk-meniny.csv');

        if (is_file($path) && ($handle = fopen($path, 'r')) !== false) {
            while (($row = fgetcsv($handle, escape: '')) !== false) {
                if (count($row) < 2) {
                    continue;
                }
                $key = trim((string) $row[0]);
                if ($key !== '') {
                    static::$map[$key] = trim((string) $row[1]);
                }
            }
            fclose($handle);
        }

        return static::$map;
    }

    /**
     * The name day for a given date, or null if none / not a real name day.
     */
    public static function for(Carbon|string $date): ?string
    {
        $carbon = $date instanceof Carbon ? $date : Carbon::parse($date);

        return static::map()[$carbon->format('m-d')] ?? null;
    }
}
