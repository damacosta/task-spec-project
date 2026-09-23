import { Link } from '@inertiajs/react';
import { ChevronLeft, ChevronRight } from 'lucide-react';

export default function Pagination({ previous, next }) {
    if (!previous && !next) return null;

    return (
        <nav className="mt-12 flex items-center justify-between gap-4 border-t border-stone-200 pt-6 text-sm dark:border-stone-800">
            {previous ? (
                <Link
                    href={previous.href}
                    prefetch
                    className="flex items-center gap-1 text-stone-600 hover:text-primary dark:text-stone-400"
                >
                    <ChevronLeft size={16} aria-hidden="true" />
                    {previous.title}
                </Link>
            ) : (
                <span />
            )}

            {next && (
                <Link
                    href={next.href}
                    prefetch
                    className="flex items-center gap-1 text-right text-stone-600 hover:text-primary dark:text-stone-400"
                >
                    {next.title}
                    <ChevronRight size={16} aria-hidden="true" />
                </Link>
            )}
        </nav>
    );
}
