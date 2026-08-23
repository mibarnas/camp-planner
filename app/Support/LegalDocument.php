<?php

namespace App\Support;

use InvalidArgumentException;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\Table\TableExtension;
use League\CommonMark\MarkdownConverter;
use RuntimeException;

class LegalDocument
{
    /** The documents that can be rendered, mapped to their route slug. */
    public const DOCUMENTS = ['privacy', 'terms'];

    /**
     * Render one of the legal documents for the given locale.
     *
     * The markdown lives in resources/legal/<doc>.<locale>.md so the text can be
     * edited without touching code. A locale with no translation falls back to
     * the app's fallback locale rather than 404ing.
     */
    public static function render(string $document, string $locale): string
    {
        if (! in_array($document, self::DOCUMENTS, true)) {
            throw new InvalidArgumentException("Unknown legal document [{$document}].");
        }

        $path = self::path($document, $locale)
            ?? self::path($document, (string) config('app.fallback_locale', 'en'))
            ?? self::path($document, 'en');

        if ($path === null) {
            throw new InvalidArgumentException("No text found for legal document [{$document}].");
        }

        // These files are ours, not user input — but tables are the only reason
        // the plain Markdown helper is not enough, so keep raw HTML escaped.
        $environment = new Environment([
            'html_input' => 'escape',
            'allow_unsafe_links' => false,
        ]);

        $environment->addExtension(new CommonMarkCoreExtension);
        $environment->addExtension(new TableExtension);

        $markdown = file_get_contents($path);

        if ($markdown === false) {
            throw new RuntimeException("Could not read legal document [{$path}].");
        }

        return (string) (new MarkdownConverter($environment))->convert($markdown);
    }

    /**
     * When the document was last touched, for the "last updated" line.
     */
    public static function updatedAt(string $document, string $locale): ?string
    {
        $path = self::path($document, $locale);

        if ($path === null) {
            return null;
        }

        $modified = filemtime($path);

        return $modified === false ? null : date('Y-m-d', $modified);
    }

    protected static function path(string $document, string $locale): ?string
    {
        $path = resource_path("legal/{$document}.{$locale}.md");

        return is_file($path) ? $path : null;
    }
}
