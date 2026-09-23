import { Link } from '@inertiajs/react';
import { Callout, Check, CodeBlock, CodeGroup, Danger, Info, Note, Tip, Warning } from '@mintlify/components';

/**
 * Components available inside every .mdx without an import, through MDXProvider.
 *
 * Only the ones the pipeline itself needs live here; the full Mintlify surface, with the
 * wrappers each broken component requires, arrives with the component map commit.
 */
export const mdxComponents = {
    Note,
    Tip,
    Warning,
    Info,
    Check,
    Danger,
    Callout,
    CodeGroup,

    // rehype-code-meta put language/filename/flags on the <pre>; CodeBlock reads them.
    pre: CodeBlock,

    a: DocsLink,
    table: ScrollableTable,
};

/** Internal links become client-side visits; anchors and external links do not. */
function DocsLink({ href = '', children, ...props }) {
    const internal = href.startsWith('/') && !href.includes('#');

    if (!internal) {
        return (
            <a href={href} {...props}>
                {children}
            </a>
        );
    }

    return (
        <Link href={href} prefetch {...props}>
            {children}
        </Link>
    );
}

/** Wide tables overflow the article on phones; the package ships no wrapper. */
function ScrollableTable(props) {
    return (
        <div className="overflow-x-auto">
            <table {...props} />
        </div>
    );
}
