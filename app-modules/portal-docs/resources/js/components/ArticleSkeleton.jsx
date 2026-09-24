/** Shown by Suspense while the page's MDX chunk loads. */
export default function ArticleSkeleton() {
    return (
        <div className="animate-pulse space-y-4" aria-hidden="true">
            <div className="h-8 w-2/3 rounded bg-stone-200 dark:bg-stone-800" />
            <div className="h-4 w-full rounded bg-stone-100 dark:bg-stone-800/60" />
            <div className="h-4 w-11/12 rounded bg-stone-100 dark:bg-stone-800/60" />
            <div className="h-4 w-4/5 rounded bg-stone-100 dark:bg-stone-800/60" />
            <div className="h-32 w-full rounded-xl bg-stone-100 dark:bg-stone-800/60" />
        </div>
    );
}
