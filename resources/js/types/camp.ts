export type CampRole = 'owner' | 'leader';

export type SlotKind = 'fixed' | 'activity';

export type CampListItem = {
    id: number;
    name: string;
    year: number;
    description: string | null;
    start_date: string | null;
    end_date: string | null;
    days_count: number;
    role: CampRole;
    owner: { id: number; name: string };
};

export type Camp = {
    id: number;
    name: string;
    year: number;
    description: string | null;
    start_date: string;
    end_date: string;
    owner_id: number;
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

export type ActivityRef = {
    id: number;
    name: string;
    category: string;
    color: string | null;
};

export type ProgramEntry = {
    id: number;
    activity_id: number | null;
    activity: ActivityRef | null;
    start_time: string; // 'HH:MM'
    duration: number; // minutes
    title: string | null;
    description: string | null;
    responsible: string | null;
    materials: string | null;
    notes: string | null;
    is_done: boolean;
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
};

export type Activity = {
    id: number;
    name: string;
    category: string;
    description: string | null;
    default_duration: number;
    color: string | null;
    materials: string | null;
    creator?: { id: number; name: string } | null;
};

export type CampMember = {
    id: number;
    name: string;
    email: string;
    role: CampRole;
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
