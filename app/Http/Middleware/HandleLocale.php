<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class HandleLocale
{
    /**
     * Pick the request's locale from the `locale` cookie, falling back to the
     * app default. The cookie is the only source: it works for guests, needs no
     * account, and survives a logout — which is what a public marketing/legal
     * page needs.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->cookie('locale');

        if (! is_string($locale) || ! in_array($locale, self::supported(), true)) {
            // No stored choice yet: fall back to what the browser asks for, so a
            // visitor who reads no Slovak is not stranded on a Slovak page before
            // they find the switcher. Their own pick always wins once made.
            $locale = $request->getPreferredLanguage(self::supported());
        }

        if (is_string($locale) && in_array($locale, self::supported(), true)) {
            App::setLocale($locale);
        }

        return $next($request);
    }

    /**
     * The locales the UI ships translations for.
     *
     * @return list<string>
     */
    public static function supported(): array
    {
        /** @var list<string> $locales */
        $locales = config('app.supported_locales', ['sk']);

        return $locales;
    }
}
