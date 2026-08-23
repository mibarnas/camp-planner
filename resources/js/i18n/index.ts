import { usePage } from '@inertiajs/vue3';
import { computed, watchEffect } from 'vue';
import type { ComputedRef } from 'vue';
import en from './locales/en.json';
import sk from './locales/sk.json';

export const LOCALES = ['sk', 'en'] as const;

export type Locale = (typeof LOCALES)[number];

export const DEFAULT_LOCALE: Locale = 'sk';

/** Human labels for the language switcher, in each language's own name. */
export const LOCALE_LABELS: Record<Locale, string> = {
    sk: 'Slovenčina',
    en: 'English',
};

type Catalog = Record<string, string>;

const catalogs: Record<Locale, Catalog> = {
    sk: sk as Catalog,
    en: en as Catalog,
};

export function isLocale(value: unknown): value is Locale {
    return (
        typeof value === 'string' &&
        (LOCALES as readonly string[]).includes(value)
    );
}

/**
 * Blade renders `<html lang="…">` from the server-side locale, so reading it
 * back is correct on first paint — no extra prop plumbing, and no flash of the
 * wrong language. Switching the language does a full reload, so this module is
 * re-evaluated with the new value and `t()` stays usable outside `setup()`.
 */
function detect(): Locale {
    if (typeof document === 'undefined') {
        return DEFAULT_LOCALE;
    }

    const lang = document.documentElement.lang?.split('-')[0];

    return isLocale(lang) ? lang : DEFAULT_LOCALE;
}

let active: Locale = detect();

export function currentLocale(): Locale {
    return active;
}

export function setActiveLocale(locale: Locale): void {
    active = locale;
}

/**
 * Pick a plural form. Strings hold their forms pipe-separated, ordered from the
 * smallest count up: English needs two ("day|days"), Slovak needs three —
 * 1 ("deň"), 2–4 ("dni"), and 0 or 5+ ("dní").
 */
function plural(forms: string[], count: number, locale: Locale): string {
    if (locale === 'sk') {
        if (count === 1) {
            return forms[0];
        }

        if (count >= 2 && count <= 4) {
            return forms[1] ?? forms[0];
        }

        return forms[2] ?? forms[1] ?? forms[0];
    }

    return count === 1 ? forms[0] : (forms[1] ?? forms[0]);
}

export type Replacements = Record<string, string | number>;

/**
 * Translate `key` in the given locale. An unknown key falls back to the other
 * locale and finally to the key itself, so a missing string degrades to
 * something readable instead of a blank in the UI.
 */
export function translate(
    locale: Locale,
    key: string,
    replace?: Replacements,
): string {
    const fallback = locale === DEFAULT_LOCALE ? 'en' : DEFAULT_LOCALE;
    let line = catalogs[locale][key] ?? catalogs[fallback][key] ?? key;

    if (line.includes('|')) {
        const count = Number(replace?.count ?? 0);
        line = plural(line.split('|'), count, locale);
    }

    if (replace) {
        for (const [token, value] of Object.entries(replace)) {
            line = line.replaceAll(`:${token}`, String(value));
        }
    }

    return line;
}

/**
 * Translate with the active locale.
 *
 * Prefer `useI18n()` inside components: it binds the locale to the current
 * request's Inertia props, which is what makes server-side rendering produce
 * the right language. This bare form is for code that runs outside a component.
 */
export function t(key: string, replace?: Replacements): string {
    return translate(active, key, replace);
}

export type UseI18nReturn = {
    t: typeof t;
    locale: ComputedRef<Locale>;
};

/**
 * The locale for the current page, taken from the Inertia props the server
 * shares on every response.
 *
 * Reading it from the props rather than from the DOM is what keeps SSR honest:
 * under SSR there is no `document` to sniff, and module state is shared between
 * requests, so a DOM-derived locale would render every visitor's page in the
 * default language. Resolving it here also refreshes the module-level locale
 * that `t()` and `dateLocale()` fall back to, which is safe because a render
 * pass is synchronous.
 */
export function useI18n(): UseI18nReturn {
    const page = usePage();

    const locale = computed<Locale>(() => {
        const fromProps = page.props.locale;

        return isLocale(fromProps) ? fromProps : detect();
    });

    setActiveLocale(locale.value);

    watchEffect(() => setActiveLocale(locale.value));

    return {
        t: (key, replace) => translate(locale.value, key, replace),
        locale,
    };
}
