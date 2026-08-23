import { router } from '@inertiajs/vue3';
import { ref } from 'vue';

/**
 * Whether the onboarding carousel is showing.
 *
 * Module-level rather than component-local because it has two triggers: the
 * server flashes `camp_onboarding` after a camp is created — which can fire
 * before the destination page has mounted — and the sidebar's help button
 * opens it again on demand. The dialog itself lives in the app layout, so it
 * is mounted for both.
 */
export const campOnboardingOpen = ref(false);

export function initializeCampOnboarding(): void {
    router.on('flash', (event) => {
        if ((event as CustomEvent).detail?.flash?.camp_onboarding) {
            campOnboardingOpen.value = true;
        }
    });
}
