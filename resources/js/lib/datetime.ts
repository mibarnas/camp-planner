import { currentLocale } from '@/i18n';

/**
 * BCP 47 tags for `Intl`, keyed by UI locale. en-GB rather than en-US so dates
 * stay day-first, which is what the rest of the app (and its users) expect.
 */
const INTL_TAGS = {
    sk: 'sk-SK',
    en: 'en-GB',
    de: 'de-DE',
} as const;

/** The `Intl` locale matching the UI language. */
export function dateLocale(): string {
    return INTL_TAGS[currentLocale()];
}
