<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Leader names are typed by hand all over the program ("Zodpovedný"), so
 * matching them has to survive diacritics, casing and stray whitespace.
 * The frontend twin of this lives in resources/js/lib/leaders.ts.
 */
class NameMatcher
{
    public static function normalize(?string $name): string
    {
        if ($name === null) {
            return '';
        }

        return (string) preg_replace('/\s+/u', ' ', trim(Str::lower(Str::ascii($name))));
    }

    public static function matches(?string $a, ?string $b): bool
    {
        $normalized = self::normalize($a);

        return $normalized !== '' && $normalized === self::normalize($b);
    }
}
