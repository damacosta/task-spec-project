import { Head, usePage } from '@inertiajs/react';
import { lazy, Suspense } from 'react';
import ArticleSkeleton from '../../components/ArticleSkeleton';
import Pagination from '../../components/Pagination';
import TableOfContents from '../../components/TableOfContents';
import DocsLayout from '../../DocsLayout';

/* One chunk per page. The glob path is relative to THIS file, not to the project root. */
const modules = import.meta.glob('../../../../content/**/*.mdx');
const cache = new Map();

/**
 * lazy() has to be memoised outside the render: a fresh lazy() on every render makes
 * React unmount and remount the article, which also resets the table of contents.
 */
function pageComponent(slug) {
    const key = `../../../../content/${slug}.mdx`;

    if (!cache.has(key)) {
        cache.set(key, lazy(modules[key]));
    }

    return cache.get(key);
}

export default function Show() {
    const { config, page, previous, next } = usePage().props;
    const Content = pageComponent(page.slug);
    const wide = page.mode === 'wide';

    return (
        <>
            <Head title={`${page.title} - ${config.name}`}>
                {page.description && <meta name="description" content={page.description} />}
            </Head>

            <main className="min-w-0 flex-1 py-10">
                <article className={`prose prose-stone dark:prose-invert ${wide ? 'max-w-none' : 'max-w-3xl'}`}>
                    <p className="text-primary mb-2 text-sm font-medium not-prose">{page.group}</p>
                    <h1 className="mb-2">{page.title}</h1>
                    {page.description && (
                        <p className="not-prose mb-8 text-lg text-stone-500 dark:text-stone-400">{page.description}</p>
                    )}

                    <Suspense fallback={<ArticleSkeleton />}>
                        <Content />
                    </Suspense>
                </article>

                <div className={wide ? 'max-w-none' : 'max-w-3xl'}>
                    <Pagination previous={previous} next={next} />
                </div>
            </main>

            {!wide && (
                <aside className="sticky top-28 hidden h-[calc(100vh-7rem)] w-64 shrink-0 overflow-y-auto py-10 xl:block">
                    <TableOfContents />
                </aside>
            )}
        </>
    );
}

Show.layout = (page) => <DocsLayout>{page}</DocsLayout>;
