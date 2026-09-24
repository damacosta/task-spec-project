import { Check, ChevronDown, Copy, ExternalLink } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { contextualOptions } from '../lib/contextual';

/**
 * "Copiar página" and the handoff links. Every entry reads the same `{slug}.md` the
 * portal already serves, so a model gets the page without the layout around it.
 */
export default function ContextMenu({ allowed, pageUrl, markdownUrl }) {
    const [open, setOpen] = useState(false);
    const [copied, setCopied] = useState(false);
    const container = useRef(null);

    const options = contextualOptions(allowed ?? [], pageUrl, markdownUrl);
    const canCopy = options.some((option) => option.id === 'copy');
    const links = options.filter((option) => option.id !== 'copy');

    useEffect(() => {
        if (!open) return undefined;

        const onPointerDown = (event) => {
            if (!container.current?.contains(event.target)) setOpen(false);
        };
        const onKeyDown = (event) => event.key === 'Escape' && setOpen(false);

        document.addEventListener('pointerdown', onPointerDown);
        document.addEventListener('keydown', onKeyDown);

        return () => {
            document.removeEventListener('pointerdown', onPointerDown);
            document.removeEventListener('keydown', onKeyDown);
        };
    }, [open]);

    if (options.length === 0) return null;

    async function copyPage() {
        try {
            const response = await fetch(markdownUrl);
            await navigator.clipboard.writeText(await response.text());
            setCopied(true);
            setTimeout(() => setCopied(false), 2000);
        } catch {
            // Clipboard needs a secure context; the "view as markdown" entry still works.
        }

        setOpen(false);
    }

    return (
        <div className="not-prose relative inline-flex" ref={container}>
            <div className="flex items-center divide-x divide-stone-200 rounded-lg border border-stone-200 text-sm dark:divide-stone-700 dark:border-stone-700">
                {canCopy && (
                    <button
                        type="button"
                        onClick={copyPage}
                        className="flex items-center gap-1.5 px-2.5 py-1.5 text-stone-600 hover:text-stone-900 dark:text-stone-400 dark:hover:text-stone-100"
                    >
                        {copied ? <Check size={14} /> : <Copy size={14} />}
                        {copied ? 'Copiado' : 'Copiar página'}
                    </button>
                )}

                {links.length > 0 && (
                    <button
                        type="button"
                        onClick={() => setOpen((value) => !value)}
                        aria-label="Mais opções"
                        aria-expanded={open}
                        className="px-1.5 py-1.5 text-stone-500 hover:text-stone-900 dark:text-stone-400 dark:hover:text-stone-100"
                    >
                        <ChevronDown size={14} />
                    </button>
                )}
            </div>

            {open && (
                <div className="dark:bg-background-dark absolute top-full right-0 z-20 mt-1 w-60 rounded-xl border border-stone-200 bg-white py-1 shadow-lg dark:border-stone-700">
                    {links.map((option) => (
                        <a
                            key={option.id}
                            href={option.href}
                            target="_blank"
                            rel="noreferrer"
                            onClick={() => setOpen(false)}
                            className="flex items-center justify-between gap-2 px-3 py-2 text-sm text-stone-600 hover:bg-stone-50 dark:text-stone-400 dark:hover:bg-stone-800/60"
                        >
                            {option.label}
                            <ExternalLink size={13} aria-hidden="true" />
                        </a>
                    ))}
                </div>
            )}
        </div>
    );
}
