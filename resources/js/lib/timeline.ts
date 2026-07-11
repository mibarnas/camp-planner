import type { ProgramEntry, TimeSlot } from '@/types/camp';

/** 'HH:MM' -> minutes since midnight. */
export function timeToMin(t: string): number {
    const [h, m] = t.split(':').map(Number);
    return h * 60 + (m || 0);
}

/** minutes since midnight -> 'HH:MM' (clamped to a single day). */
export function minToTime(min: number): string {
    const clamped = Math.max(0, Math.min(1439, Math.round(min)));
    const h = Math.floor(clamped / 60);
    const m = clamped % 60;
    return `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}`;
}

/** Human label for a duration in minutes, e.g. 90 -> "1 h 30 min". */
export function durationLabel(min: number): string {
    const h = Math.floor(min / 60);
    const m = min % 60;
    if (h && m) return `${h} h ${m} min`;
    if (h) return `${h} h`;
    return `${m} min`;
}

export type DayBounds = { start: number; end: number };

/**
 * Overall visible time window: from the start of the first block to the end of
 * the last one (stretched further only if an activity sticks out).
 */
export function dayBounds(slots: TimeSlot[], days: { entries: ProgramEntry[] }[]): DayBounds {
    let start = Infinity;
    let end = -Infinity;
    for (const s of slots) {
        start = Math.min(start, timeToMin(s.start_time));
        end = Math.max(end, timeToMin(s.end_time));
    }
    for (const d of days) {
        for (const e of d.entries) {
            start = Math.min(start, timeToMin(e.start_time));
            end = Math.max(end, timeToMin(e.start_time) + e.duration);
        }
    }
    if (!Number.isFinite(start) || !Number.isFinite(end) || end <= start) {
        return { start: 8 * 60, end: 16 * 60 + 30 };
    }
    return { start, end };
}

export type LaidOutEntry = { entry: ProgramEntry; lane: number };

/**
 * Greedy lane assignment so overlapping activities stack instead of colliding.
 * Returns the entries with a lane index and the total number of lanes used.
 */
export function assignLanes(entries: ProgramEntry[]): { items: LaidOutEntry[]; lanes: number } {
    const sorted = [...entries].sort(
        (a, b) => timeToMin(a.start_time) - timeToMin(b.start_time) || b.duration - a.duration,
    );
    const laneEnds: number[] = [];
    const items: LaidOutEntry[] = [];

    for (const entry of sorted) {
        const start = timeToMin(entry.start_time);
        const end = start + entry.duration;
        let lane = laneEnds.findIndex((e) => e <= start);
        if (lane === -1) {
            lane = laneEnds.length;
            laneEnds.push(end);
        } else {
            laneEnds[lane] = end;
        }
        items.push({ entry, lane });
    }

    return { items, lanes: Math.max(1, laneEnds.length) };
}

/** The block whose window contains a given start minute, if any. */
export function containingSlot(startMin: number, slots: TimeSlot[]): TimeSlot | null {
    return (
        slots.find((s) => startMin >= timeToMin(s.start_time) && startMin < timeToMin(s.end_time)) ??
        null
    );
}

/**
 * How many minutes an activity spills past the block that its start sits in.
 * 0 when it fits (or starts in free time between blocks).
 */
export function overflowMinutes(entry: ProgramEntry, slots: TimeSlot[]): number {
    const start = timeToMin(entry.start_time);
    const slot = containingSlot(start, slots);
    if (!slot) return 0;
    const end = start + entry.duration;
    return Math.max(0, end - timeToMin(slot.end_time));
}
