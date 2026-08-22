import type { CampLeader } from '@/types/camp';

/**
 * Leader names are typed by hand ("Zodpovedný"), so recognising one has to
 * survive diacritics, casing and stray whitespace. The server twin of this
 * lives in App\Support\NameMatcher.
 */
export function normalizeName(name: string | null | undefined): string {
    if (!name) {
        return '';
    }

    return name
        .normalize('NFD')
        .replace(/\p{Diacritic}/gu, '')
        .toLowerCase()
        .trim()
        .replace(/\s+/g, ' ');
}

/** The camp leader this free-text name refers to, if it names one at all. */
export function matchLeader(
    name: string | null | undefined,
    leaders: CampLeader[],
): CampLeader | null {
    const needle = normalizeName(name);

    if (!needle) {
        return null;
    }

    return (
        leaders.find((leader) => normalizeName(leader.name) === needle) ?? null
    );
}
