<?php

namespace App\Support;

use InvalidArgumentException;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\MarkdownConverter;
use RuntimeException;

class ChangelogDocument
{
    /**
     * Render the "what's new" text for a released version.
     *
     * The markdown lives in resources/changelog/<version>.<locale>.md so a
     * release note can be written without touching code. A locale with no
     * translation falls back to the app's fallback locale, like the legal pages.
     */
    public static function render(string $version, string $locale): string
    {
        self::assertVersion($version);

        $path = self::path($version, $locale)
            ?? self::path($version, (string) config('app.fallback_locale', 'en'))
            ?? self::path($version, 'en');

        if ($path === null) {
            throw new InvalidArgumentException("No changelog text found for version [{$version}].");
        }

        // These files are ours, not user input, but there is no reason for a
        // release note to contain raw HTML — keep it escaped either way.
        $environment = new Environment([
            'html_input' => 'escape',
            'allow_unsafe_links' => false,
        ]);

        $environment->addExtension(new CommonMarkCoreExtension);

        $markdown = file_get_contents($path);

        if ($markdown === false) {
            throw new RuntimeException("Could not read changelog [{$path}].");
        }

        return (string) (new MarkdownConverter($environment))->convert($markdown);
    }

    /**
     * Whether any translation of this version's changelog exists.
     *
     * Lets the version be bumped before its text is written without breaking
     * every page — the dialog simply does not appear.
     */
    public static function exists(string $version): bool
    {
        if (! self::isVersion($version)) {
            return false;
        }

        foreach (config('app.supported_locales', ['en']) as $locale) {
            if (self::path($version, $locale) !== null) {
                return true;
            }
        }

        return self::path($version, 'en') !== null;
    }

    protected static function assertVersion(string $version): void
    {
        if (! self::isVersion($version)) {
            throw new InvalidArgumentException("Invalid changelog version [{$version}].");
        }
    }

    /** The version doubles as a filename, so keep it to digits and dots. */
    protected static function isVersion(string $version): bool
    {
        return preg_match('/^\d+\.\d+\.\d+$/', $version) === 1;
    }

    protected static function path(string $version, string $locale): ?string
    {
        $path = resource_path("changelog/{$version}.{$locale}.md");

        return is_file($path) ? $path : null;
    }
}
