import { Link, usePage } from '@inertiajs/react';
import { MDXProvider } from '@mdx-js/react';
import { useEffect } from 'react';
import MobileNav from './components/MobileNav';
import Sidebar from './components/Sidebar';
import ThemeToggle from './components/ThemeToggle';
import { mdxComponents } from './mdx-components';

/**
 * Persistent shell: mounted once and kept across client-side visits, so the sidebar
 * keeps its scroll position and the theme is never re-applied mid-navigation.
 */
export default function DocsLayout({ children }) {
    const { config, navigation, page } = usePage().props;
    const tabs = navigation ?? [];
    const activeTab = tabs.find((tab) => tab.tab === page.tab) ?? tabs[0];

    /*
     * docs.json colors reach Tailwind through these vars, so bg-primary/10 and
     * text-primary follow the config without a rebuild. They go on the root element and
     * not on a wrapper because the mobile menu renders into the body through a portal,
     * outside any wrapper this component could set them on.
     */
    useEffect(() => {
        const root = document.documentElement;

        root.style.setProperty('--color-primary', config.colors.primary);
        root.style.setProperty('--color-primary-light', config.colors.light);
        root.style.setProperty('--color-primary-dark', config.colors.dark);
    }, [config.colors]);

    return (
        <MDXProvider components={mdxComponents}>
            <div className="min-h-screen">
                <header className="sticky top-0 z-30 border-b border-stone-200 bg-white/90 backdrop-blur dark:border-stone-800 dark:bg-background-dark/90">
                    <div className="mx-auto flex h-16 max-w-[100rem] items-center gap-2 px-4">
                        <MobileNav
                            groups={activeTab?.groups ?? []}
                            anchors={config.anchors ?? []}
                            currentSlug={page.slug}
                        />

                        <Link href={`/${config.prefix ?? 'docs'}`} className="font-semibold">
                            {config.name}
                        </Link>
                        <div className="ml-auto">
                            <ThemeToggle strict={config.strictAppearance} />
                        </div>
                    </div>

                    <nav className="mx-auto flex h-12 max-w-[100rem] items-end gap-6 px-4 text-sm">
                        {tabs.map((tab) => (
                            <Link
                                key={tab.tab}
                                href={tab.groups[0].pages[0].href}
                                prefetch={['mount', 'hover']}
                                cacheFor="5m"
                                className={`-mb-px border-b-2 pb-2 ${
                                    tab.tab === page.tab
                                        ? 'border-primary text-primary'
                                        : 'border-transparent text-stone-500 hover:text-stone-900 dark:text-stone-400 dark:hover:text-stone-100'
                                }`}
                            >
                                {tab.tab}
                            </Link>
                        ))}
                    </nav>
                </header>

                <div className="mx-auto flex max-w-[100rem] gap-8 px-4">
                    <aside className="sticky top-28 hidden h-[calc(100vh-7rem)] w-72 shrink-0 overflow-y-auto py-8 lg:block">
                        <Sidebar
                            groups={activeTab?.groups ?? []}
                            anchors={config.anchors ?? []}
                            currentSlug={page.slug}
                        />
                    </aside>

                    {children}
                </div>
            </div>
        </MDXProvider>
    );
}
