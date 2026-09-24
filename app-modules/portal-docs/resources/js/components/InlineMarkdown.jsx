import { Fragment } from 'react';

const INLINE = /(`[^`]+`|\*\*[^*]+\*\*|\[[^\]]+\]\([^)]+\))/g;

/**
 * Renders the small subset of Markdown that OpenAPI descriptions actually use.
 * A full Markdown pipeline here would pull a parser into every field row for the sake
 * of three constructs.
 */
export default function InlineMarkdown({ text, className = '' }) {
    if (!text) return null;

    return (
        <div className={className}>
            {text.split(/\n{2,}/).map((paragraph, index) => (
                <p key={index} className={index > 0 ? 'mt-2' : undefined}>
                    {renderInline(paragraph)}
                </p>
            ))}
        </div>
    );
}

function renderInline(text) {
    return text.split(INLINE).map((part, index) => {
        if (part.startsWith('`') && part.endsWith('`')) {
            return (
                <code
                    key={index}
                    className="rounded bg-stone-100 px-1 py-0.5 font-mono text-[0.8125em] dark:bg-stone-800"
                >
                    {part.slice(1, -1)}
                </code>
            );
        }

        if (part.startsWith('**') && part.endsWith('**')) {
            return <strong key={index}>{part.slice(2, -2)}</strong>;
        }

        const link = /^\[([^\]]+)\]\(([^)]+)\)$/.exec(part);

        if (link) {
            return (
                <a key={index} href={link[2]} className="text-primary underline underline-offset-2">
                    {link[1]}
                </a>
            );
        }

        return <Fragment key={index}>{part}</Fragment>;
    });
}
