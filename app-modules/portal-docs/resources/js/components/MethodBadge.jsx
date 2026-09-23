const COLORS = {
    GET: 'bg-sky-50 text-sky-700 dark:bg-sky-400/10 dark:text-sky-300',
    POST: 'bg-emerald-50 text-emerald-700 dark:bg-emerald-400/10 dark:text-emerald-300',
    PUT: 'bg-amber-50 text-amber-700 dark:bg-amber-400/10 dark:text-amber-300',
    PATCH: 'bg-amber-50 text-amber-700 dark:bg-amber-400/10 dark:text-amber-300',
    DELETE: 'bg-rose-50 text-rose-700 dark:bg-rose-400/10 dark:text-rose-300',
};

/** DELETE is abbreviated in the sidebar so the label never wraps at 18rem. */
const SHORT = { DELETE: 'DEL', OPTIONS: 'OPT' };

export default function MethodBadge({ method, size = 'sm' }) {
    if (!method) return null;

    const upper = method.toUpperCase();
    const classes = COLORS[upper] ?? 'bg-stone-100 text-stone-600 dark:bg-stone-700 dark:text-stone-300';

    return (
        <span
            className={`inline-flex shrink-0 items-center rounded font-mono font-semibold uppercase ${classes} ${
                size === 'sm' ? 'px-1.5 py-0.5 text-[0.625rem]' : 'px-2 py-1 text-xs'
            }`}
        >
            {size === 'sm' ? (SHORT[upper] ?? upper) : upper}
        </span>
    );
}
