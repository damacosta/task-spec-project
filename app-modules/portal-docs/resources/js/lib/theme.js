const STORAGE_KEY = 'portal-docs-theme';

/** Reads what the inline script in the root view already applied. */
export function currentTheme() {
    return document.documentElement.classList.contains('dark') ? 'dark' : 'light';
}

export function applyTheme(theme) {
    document.documentElement.classList.toggle('dark', theme === 'dark');

    try {
        localStorage.setItem(STORAGE_KEY, theme);
    } catch {
        // Private windows throw here; the theme still applies for this page view.
    }
}

/**
 * `appearance.strict` in docs.json means the site ships one theme and the reader does
 * not get a toggle.
 */
export function resolveInitialTheme({ appearance, strictAppearance }) {
    if (strictAppearance && appearance !== 'system') return appearance;
    return currentTheme();
}
