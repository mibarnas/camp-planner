/**
 * The release notes for a version the current user has not acknowledged yet.
 * Shared on every page (see HandleInertiaRequests), null once dismissed.
 */
export type Changelog = {
    version: string;
    html: string;
};
