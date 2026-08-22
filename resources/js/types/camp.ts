export type CampRole = 'owner' | 'leader';

export type SlotKind = 'fixed' | 'activity';

export type CampListItem = {
    id: number;
    name: string;
    icon: string;
    color: string;
    year: number;
    description: string | null;
    location: string | null;
    start_date: string | null;
    end_date: string | null;
    days_count: number;
    role: CampRole;
    owner: { id: number; name: string };
};

export type Camp = {
    id: number;
    name: string;
    icon: string;
    color: string;
    year: number;
    description: string | null;
    location: string | null;
    start_date: string;
    end_date: string;
    owner_id: number;
    is_owner: boolean;
    schedule_locked: boolean;
};

/** The camp the current page belongs to — shared globally so the sidebar can show its section. */
export type CampContext = {
    id: number;
    name: string;
    icon: string;
    color: string;
    is_owner: boolean;
};

export type TimeSlot = {
    id: number;
    name: string;
    start_time: string;
    end_time: string;
    kind: SlotKind;
    color: string | null;
    position: number;
};

/** One day's deviation from a camp-wide block; null times keep the template's. */
export type SlotOverride = {
    time_slot_id: number;
    start_time: string | null;
    end_time: string | null;
    is_hidden: boolean;
};

/**
 * A block as it actually applies to one day, template merged with its override.
 * Derived on the client — `effectiveSlots()` is the only place that builds it.
 */
export type EffectiveSlot = TimeSlot & { overridden: boolean; hidden: boolean };

export type SlotOverridePatch = {
    start_time?: string | null;
    end_time?: string | null;
    is_hidden?: boolean;
};

export type PlanVersion = {
    id: number;
    name: string;
    author: string | null;
    created_at: string | null;
};

/** The stored AI summary for one scope: a day, or the whole camp when camp_day_id is null. */
export type AiSummary = {
    camp_day_id: number | null;
    summary: string;
    summary_html: string;
    author: string | null;
    saved_at: string | null;
};

/** The minimum a day needs to be offered as an AI-summary scope. */
export type SummaryDay = {
    id: number;
    date: string;
    weekday: string;
    label: string;
    entries_count: number;
    reviewers: number;
};

export type FeedbackRating = {
    entry_id: number;
    entry_title: string;
    rating: number;
    reason: string | null;
};

export type FeedbackAnswer = {
    question_id: number;
    answer: string;
};

/** One leader's whole review of one day, as shown on the Feedback page. */
export type LeaderFeedback = {
    id: number;
    user_name: string;
    notes: string | null;
    camp_rating: number | null;
    camp_reason: string | null;
    ratings: FeedbackRating[];
    answers: FeedbackAnswer[];
};

export type FeedbackDay = SummaryDay & {
    is_last: boolean;
    avg: number | null;
    reviews: LeaderFeedback[];
};

export type ActivityRef = {
    id: number;
    name: string;
    category: string;
    color: string | null;
};

/** 'todo' = treba doriešiť, 'none' = rozpracované (default), 'done' = hotové. */
export type EntryStatus = 'todo' | 'none' | 'done';

/**
 * 'detailed' = a programme activity: can come from the activity library or be
 * saved back to it, carries a scenario, materials and a responsible leader,
 * and gets rated in the day review.
 * 'simple' = a plain block on the timeline (Raňajky, Presun do Tatier).
 */
export type EntryKind = 'detailed' | 'simple';

/**
 * How an activity scores groups: 'raw' counts the entered numbers directly,
 * 'placement' turns the ranking of those numbers into points.
 */
export type PointsMode = 'none' | 'raw' | 'placement';

export type EntryGroupPoint = {
    camp_group_id: number;
    value: number;
};

export type ProgramEntry = {
    id: number;
    activity_id: number | null;
    kind: EntryKind;
    activity: ActivityRef | null;
    start_time: string; // 'HH:MM'
    duration: number; // minutes
    title: string | null;
    description: string | null;
    responsible: string | null;
    materials: string | null;
    notes: string | null;
    status: EntryStatus;
    points_mode: PointsMode;
    points: EntryGroupPoint[];
    avg_rating: number | null;
    rating_count: number;
};

export type EntryRating = { rating: number; reason: string | null };

/**
 * A camp's own review question. 'day' questions are asked in every day's
 * review, 'camp' questions once as part of the last day's.
 */
export type FeedbackQuestion = {
    id: number;
    scope: 'day' | 'camp';
    text: string;
    position: number;
    archived?: boolean;
};

export type DayReviewData = {
    notes: string | null;
    camp_rating: number | null;
    camp_reason: string | null;
    ratings: Record<number, EntryRating>;
    answers: Record<number, string>;
};

export type ReviewSummary = {
    reviewers: number;
    avg: number | null;
    camp_avg: number | null;
};

export type CampDay = {
    id: number;
    date: string;
    weekday: string;
    label: string;
    is_trip: boolean;
    trip_name: string | null;
    name_days: string | null;
    birthdays: string | null;
    materials: string | null;
    notes: string | null;
    entries: ProgramEntry[];
    slot_overrides: SlotOverride[];
    is_last: boolean;
    my_review: DayReviewData | null;
    review_summary: ReviewSummary;
};

/** A competing group as the leaderboard shows it. */
export type LeaderboardGroup = {
    id: number;
    name: string;
    color: string | null;
    type_name: string | null;
    leaders: string[];
};

export type Standing = {
    group_id: number;
    total: number;
};

/** One group's result in one scoring activity; `awarded` is derived server-side. */
export type PointRow = {
    group_id: number;
    value: number | null;
    awarded: number | null;
};

export type ScoringEntry = {
    id: number;
    title: string;
    start_time: string;
    points_mode: PointsMode;
    rows: PointRow[];
};

export type ScoringDay = {
    id: number;
    date: string;
    weekday: string;
    label: string;
    entries: ScoringEntry[];
};

export type ActivityCategory = {
    id: number;
    name: string;
    color: string | null;
};

export type Activity = {
    id: number;
    name: string;
    category_id: number | null;
    description: string | null;
    default_duration: number;
    color: string | null;
    materials: string | null;
    creator?: { id: number; name: string } | null;
    usage_count?: number;
    created_at?: string | null;
    share_url?: string;
};

export type CampMember = {
    id: number;
    name: string;
    email: string;
    role: CampRole;
};

/**
 * A leader of the camp. `user_id` is null for someone without an account —
 * still referenceable as the person responsible for an activity.
 */
export type CampLeader = {
    id: number;
    name: string;
    user_id: number | null;
    color: string | null;
    email?: string | null;
};

/** A camp-defined kind of group: "Detské skupiny", "Fotografi", … */
export type GroupType = {
    id: number;
    name: string;
    color: string | null;
    position: number;
};

export type CampGroup = {
    id: number;
    name: string;
    group_type_id: number | null;
    competes: boolean;
    color: string | null;
    position: number;
    leader_ids: number[];
};

export type CampInvitation = {
    id: number;
    email: string;
    role: CampRole;
    link: string;
};

export type ShareLink = {
    id: number;
    link: string;
};

export type ActivityLibraryRef = {
    id: number;
    name: string;
};

export type ActivityLibrary = {
    id: number;
    name: string;
    activities_count: number;
    is_owner: boolean;
};

export type LibraryMember = {
    id: number;
    name: string;
    email: string;
    role: 'owner' | 'member';
};
