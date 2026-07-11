// Named colours used for time blocks and activity categories. The full class
// strings are written out literally so Tailwind's compiler keeps them.

export type ColorStyle = {
    /** soft filled cell background + border */
    cell: string;
    /** small chip / badge */
    chip: string;
    /** solid dot */
    dot: string;
    /** column header accent */
    header: string;
};

const COLORS: Record<string, ColorStyle> = {
    sky: {
        cell: 'bg-sky-50 border-sky-200 dark:bg-sky-950/40 dark:border-sky-900',
        chip: 'bg-sky-100 text-sky-800 dark:bg-sky-950 dark:text-sky-200',
        dot: 'bg-sky-500',
        header: 'text-sky-700 dark:text-sky-300',
    },
    violet: {
        cell: 'bg-violet-50 border-violet-200 dark:bg-violet-950/40 dark:border-violet-900',
        chip: 'bg-violet-100 text-violet-800 dark:bg-violet-950 dark:text-violet-200',
        dot: 'bg-violet-500',
        header: 'text-violet-700 dark:text-violet-300',
    },
    emerald: {
        cell: 'bg-emerald-50 border-emerald-200 dark:bg-emerald-950/40 dark:border-emerald-900',
        chip: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-200',
        dot: 'bg-emerald-500',
        header: 'text-emerald-700 dark:text-emerald-300',
    },
    amber: {
        cell: 'bg-amber-50 border-amber-200 dark:bg-amber-950/40 dark:border-amber-900',
        chip: 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-200',
        dot: 'bg-amber-500',
        header: 'text-amber-700 dark:text-amber-300',
    },
    slate: {
        cell: 'bg-slate-100 border-slate-200 dark:bg-slate-800/40 dark:border-slate-700',
        chip: 'bg-slate-200 text-slate-800 dark:bg-slate-800 dark:text-slate-200',
        dot: 'bg-slate-500',
        header: 'text-slate-600 dark:text-slate-300',
    },
    rose: {
        cell: 'bg-rose-50 border-rose-200 dark:bg-rose-950/40 dark:border-rose-900',
        chip: 'bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-200',
        dot: 'bg-rose-500',
        header: 'text-rose-700 dark:text-rose-300',
    },
    blue: {
        cell: 'bg-blue-50 border-blue-200 dark:bg-blue-950/40 dark:border-blue-900',
        chip: 'bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-200',
        dot: 'bg-blue-500',
        header: 'text-blue-700 dark:text-blue-300',
    },
    orange: {
        cell: 'bg-orange-50 border-orange-200 dark:bg-orange-950/40 dark:border-orange-900',
        chip: 'bg-orange-100 text-orange-800 dark:bg-orange-950 dark:text-orange-200',
        dot: 'bg-orange-500',
        header: 'text-orange-700 dark:text-orange-300',
    },
    teal: {
        cell: 'bg-teal-50 border-teal-200 dark:bg-teal-950/40 dark:border-teal-900',
        chip: 'bg-teal-100 text-teal-800 dark:bg-teal-950 dark:text-teal-200',
        dot: 'bg-teal-500',
        header: 'text-teal-700 dark:text-teal-300',
    },
    fuchsia: {
        cell: 'bg-fuchsia-50 border-fuchsia-200 dark:bg-fuchsia-950/40 dark:border-fuchsia-900',
        chip: 'bg-fuchsia-100 text-fuchsia-800 dark:bg-fuchsia-950 dark:text-fuchsia-200',
        dot: 'bg-fuchsia-500',
        header: 'text-fuchsia-700 dark:text-fuchsia-300',
    },
    lime: {
        cell: 'bg-lime-50 border-lime-200 dark:bg-lime-950/40 dark:border-lime-900',
        chip: 'bg-lime-100 text-lime-800 dark:bg-lime-950 dark:text-lime-200',
        dot: 'bg-lime-500',
        header: 'text-lime-700 dark:text-lime-300',
    },
    cyan: {
        cell: 'bg-cyan-50 border-cyan-200 dark:bg-cyan-950/40 dark:border-cyan-900',
        chip: 'bg-cyan-100 text-cyan-800 dark:bg-cyan-950 dark:text-cyan-200',
        dot: 'bg-cyan-500',
        header: 'text-cyan-700 dark:text-cyan-300',
    },
    indigo: {
        cell: 'bg-indigo-50 border-indigo-200 dark:bg-indigo-950/40 dark:border-indigo-900',
        chip: 'bg-indigo-100 text-indigo-800 dark:bg-indigo-950 dark:text-indigo-200',
        dot: 'bg-indigo-500',
        header: 'text-indigo-700 dark:text-indigo-300',
    },
};

const FALLBACK: ColorStyle = {
    cell: 'bg-muted/50 border-border',
    chip: 'bg-muted text-muted-foreground',
    dot: 'bg-muted-foreground',
    header: 'text-muted-foreground',
};

export const COLOR_NAMES = Object.keys(COLORS);

export function colorStyle(name: string | null | undefined): ColorStyle {
    if (!name) {
        return FALLBACK;
    }
    return COLORS[name] ?? FALLBACK;
}

// User-defined activity categories (tags) come from the backend, scoped to a
// library. This shape mirrors the serialized `activity_categories` rows.
export type Category = { id: number; name: string; color: string | null };

export function categoryById(
    categories: Category[],
    id: number | null | undefined,
): Category | null {
    if (id == null) return null;
    return categories.find((c) => c.id === id) ?? null;
}
