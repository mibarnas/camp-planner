<?php

namespace App\Support;

use League\CommonMark\CommonMarkConverter;

class Markdown
{
    /**
     * Render model-generated markdown as HTML safe to drop into the page.
     *
     * Escapes raw HTML rather than passing it through: the input comes from an
     * LLM, so it is never trusted.
     */
    public static function toHtml(string $markdown): string
    {
        return (string) (new CommonMarkConverter([
            'html_input' => 'escape',
            'allow_unsafe_links' => false,
        ]))->convert($markdown);
    }
}
