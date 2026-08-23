import { router } from '@inertiajs/vue3';
import { ref } from 'vue';

/**
 * Set when the server flashes `camp_onboarding` after a camp is created.
 *
 * The `flash` event can fire before the destination page component mounts, so
 * the signal is parked here rather than handled inline — `camps/Show` consumes
 * it with an immediate watcher and clears it, making it a one-shot.
 */
export const pendingCampOnboarding = ref(false);

export function initializeCampOnboarding(): void {
    router.on('flash', (event) => {
        if ((event as CustomEvent).detail?.flash?.camp_onboarding) {
            pendingCampOnboarding.value = true;
        }
    });
}
