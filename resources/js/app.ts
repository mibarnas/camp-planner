import { createInertiaApp } from '@inertiajs/vue3';
import { initializeTheme } from '@/composables/useAppearance';
import AppLayout from '@/layouts/AppLayout.vue';
import AuthLayout from '@/layouts/AuthLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { initializeFlashToast } from '@/lib/flashToast';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    layout: (name) => {
        switch (true) {
            case name === 'Welcome':
                return null;
            case name === 'activities/Shared':
                return null;
            case name === 'invitations/Join':
                return AuthLayout;
            case name === 'libraries/Join':
                return AuthLayout;
            case name.startsWith('auth/'):
                return AuthLayout;
            case name.startsWith('settings/'):
                return [AppLayout, SettingsLayout];
            default:
                return AppLayout;
        }
    },
    progress: {
        color: '#4B5563',
    },
});

// This will set light / dark mode on page load...
initializeTheme();

// This will listen for flash toast data from the server...
initializeFlashToast();

// A deploy replaces the hashed JS chunks. A tab that was opened before the
// deploy will 404 when it lazy-loads a page chunk that no longer exists,
// leaving a blank screen. Vite fires `vite:preloadError` in that case — recover
// with a single full reload (which pulls the current manifest), guarded so a
// genuinely broken asset can never cause a reload loop.
window.addEventListener('vite:preloadError', () => {
    const key = 'vitePreloadErrorReloadedAt';
    const last = Number(sessionStorage.getItem(key) || 0);
    if (Date.now() - last < 10_000) {
        return;
    }
    sessionStorage.setItem(key, String(Date.now()));
    window.location.reload();
});
