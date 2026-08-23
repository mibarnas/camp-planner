import { ref } from 'vue';

/**
 * Whether the "what's new" dialog is showing.
 *
 * Module-level for the same reason as [campOnboardingOpen]: the dialog is
 * mounted once in the app layout, and the sidebar opens it from elsewhere in
 * the tree. It also opens itself when the server shares unread release notes.
 */
export const changelogOpen = ref(false);
