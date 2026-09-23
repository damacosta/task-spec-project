import { Moon, Sun } from 'lucide-react';
import { useState } from 'react';
import { applyTheme, currentTheme } from '../lib/theme';

export default function ThemeToggle({ strict }) {
    const [theme, setTheme] = useState(() => currentTheme());

    if (strict) return null;

    const next = theme === 'dark' ? 'light' : 'dark';

    return (
        <button
            type="button"
            onClick={() => {
                applyTheme(next);
                setTheme(next);
            }}
            aria-label={next === 'dark' ? 'Ativar tema escuro' : 'Ativar tema claro'}
            className="rounded-lg p-2 text-stone-500 hover:bg-stone-100 hover:text-stone-900 dark:text-stone-400 dark:hover:bg-stone-800 dark:hover:text-stone-100"
        >
            {theme === 'dark' ? <Moon size={18} /> : <Sun size={18} />}
        </button>
    );
}
