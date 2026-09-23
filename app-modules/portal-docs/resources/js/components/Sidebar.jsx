import { Link } from '@inertiajs/react';
import { DynamicIcon } from 'lucide-react/dynamic';
import MethodBadge from './MethodBadge';

export default function Sidebar({ groups, anchors, currentSlug }) {
    return (
        <nav className="flex flex-col gap-6 text-sm" aria-label="Documentação">
            {anchors.length > 0 && (
                <ul className="flex flex-col gap-1 border-b border-stone-200 pb-4 dark:border-stone-800">
                    {anchors.map((anchor) => (
                        <li key={anchor.href}>
                            <a
                                href={anchor.href}
                                className="flex items-center gap-2 rounded-xl px-3 py-1.5 text-stone-600 hover:bg-stone-100 dark:text-stone-400 dark:hover:bg-stone-800/60"
                            >
                                {anchor.icon && <DynamicIcon name={anchor.icon} size={16} aria-hidden="true" />}
                                {anchor.anchor}
                            </a>
                        </li>
                    ))}
                </ul>
            )}

            {groups.map((group) => (
                <div key={group.group} className="flex flex-col gap-1">
                    <h2 className="px-3 pb-1 text-xs font-semibold text-stone-900 dark:text-stone-100">
                        {group.group}
                    </h2>

                    <ul className="flex flex-col gap-0.5">
                        {group.pages.map((page) => (
                            <li key={page.slug}>
                                <Link
                                    href={page.href}
                                    prefetch
                                    cacheFor="5m"
                                    aria-current={page.slug === currentSlug ? 'page' : undefined}
                                    className={`flex items-center gap-2 rounded-xl px-3 py-1.5 ${
                                        page.slug === currentSlug
                                            ? 'bg-primary/10 text-primary font-medium'
                                            : 'text-stone-600 hover:text-stone-900 dark:text-stone-400 dark:hover:text-stone-100'
                                    }`}
                                >
                                    {page.icon && <DynamicIcon name={page.icon} size={16} aria-hidden="true" />}
                                    <span className="flex-1 truncate">{page.sidebarTitle}</span>
                                    {page.deprecated && (
                                        <span className="text-[0.625rem] uppercase text-stone-400">obsoleto</span>
                                    )}
                                    <MethodBadge method={page.method} />
                                </Link>
                            </li>
                        ))}
                    </ul>
                </div>
            ))}
        </nav>
    );
}
