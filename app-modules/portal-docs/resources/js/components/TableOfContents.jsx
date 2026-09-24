import { useEffect, useState } from 'react';

/**
 * Tracks the h2/h3 of the rendered article.
 *
 * The headings are read with a MutationObserver rather than a useEffect on the slug:
 * the MDX chunk loads lazily, so at the moment the slug changes the <article> is still
 * the skeleton and querying it returns nothing.
 */
export default function TableOfContents() {
    const [headings, setHeadings] = useState([]);
    const [activeId, setActiveId] = useState(null);

    useEffect(() => {
        const article = document.querySelector('article');
        if (!article) return undefined;

        const read = () => {
            setHeadings(
                [...article.querySelectorAll('h2[id], h3[id]')].map((node) => ({
                    id: node.id,
                    text: node.textContent,
                    level: Number(node.tagName[1]),
                })),
            );
        };

        read();

        const observer = new MutationObserver(read);
        observer.observe(article, { childList: true, subtree: true });

        return () => observer.disconnect();
    }, []);

    useEffect(() => {
        if (headings.length === 0) return undefined;

        const onScroll = () => {
            const seen = headings.filter((heading) => {
                const node = document.getElementById(heading.id);
                return node && node.getBoundingClientRect().top <= 150;
            });

            setActiveId(seen.at(-1)?.id ?? headings[0].id);
        };

        onScroll();
        window.addEventListener('scroll', onScroll, { passive: true });

        return () => window.removeEventListener('scroll', onScroll);
    }, [headings]);

    if (headings.length === 0) return null;

    return (
        <nav className="text-sm" aria-label="Nesta página">
            <p className="mb-3 font-medium text-stone-900 dark:text-stone-100">Nesta página</p>

            <ul className="flex flex-col gap-2 border-l border-stone-200 dark:border-stone-800">
                {headings.map((heading) => (
                    <li key={heading.id} style={{ paddingLeft: heading.level === 3 ? '1.5rem' : '0.75rem' }}>
                        <a
                            href={`#${heading.id}`}
                            className={
                                heading.id === activeId
                                    ? 'text-primary'
                                    : 'text-stone-500 hover:text-stone-900 dark:text-stone-400 dark:hover:text-stone-100'
                            }
                        >
                            {heading.text}
                        </a>
                    </li>
                ))}
            </ul>
        </nav>
    );
}
