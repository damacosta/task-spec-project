import { Link } from '@inertiajs/react';
import {
    Accordion,
    Badge,
    Callout,
    Card,
    Check,
    CodeBlock,
    CodeGroup,
    Color,
    Columns,
    Danger,
    Expandable,
    Frame,
    Info,
    Mermaid,
    Note,
    Panel,
    Property,
    Steps,
    Tabs,
    Tile,
    Tip,
    Tooltip,
    Tree,
    Update,
    Warning,
} from '@mintlify/components';
import { DynamicIcon } from 'lucide-react/dynamic';

/**
 * Everything available inside an .mdx without an import, through MDXProvider.
 *
 * A plain re-export means the component works as shipped. A wrapper below always exists
 * for a reason stated next to it: the package either does not export that name, resolves
 * icons through a network call, hardcodes English, or returns null without props MDX
 * authors have no way to pass.
 */
export const mdxComponents = {
    // Callouts, straight from the package.
    Note,
    Tip,
    Warning,
    Info,
    Check,
    Danger,
    Callout,

    // Layout.
    Card: DocsCard,
    Columns,
    Tile,
    Frame,
    Panel,

    // Sequences. Step, Tab and AccordionGroup are sub-components, not exported names.
    Steps,
    Step: DocsStep,
    Tabs,
    Tab: Tabs.Item,
    Accordion: DocsAccordion,
    AccordionGroup: Accordion.Group,
    Expandable: DocsExpandable,

    // Code. rehype-code-meta already put language/filename/flags on the <pre>.
    pre: CodeBlock,
    CodeGroup,

    // API prose. The package exports Property; these two names are Mintlify's own.
    Property,
    ParamField: DocsParamField,
    ResponseField: DocsResponseField,

    // Everything else.
    Badge,
    Tooltip,
    Icon: DocsIcon,
    Mermaid: DocsMermaid,
    Tree,
    Folder: Tree.Folder,
    File: Tree.File,
    Update: DocsUpdate,
    Color,
    ColorRow: Color.Row,
    ColorItem: Color.Item,

    // HTML.
    a: DocsLink,
    table: ScrollableTable,
};

/**
 * A string icon reaches the package's Font Awesome resolver, which fetches from
 * Mintlify's CDN and renders nothing offline. Every wrapper that takes an icon goes
 * through lucide instead.
 */
function lucideIcon(icon, size = 20) {
    if (typeof icon !== 'string') return icon;

    return <DynamicIcon name={icon} size={size} aria-hidden="true" />;
}

function DocsCard({ icon, className = '', ...props }) {
    // not-prose keeps the typography plugin from underlining the card's own link.
    return <Card icon={lucideIcon(icon, 24)} className={`not-prose ${className}`} {...props} />;
}

function DocsStep({ icon, ...props }) {
    return <Steps.Item icon={lucideIcon(icon)} {...props} />;
}

/** Without an explicit defaultOpen the accordion mounts in an undefined state. */
function DocsAccordion({ icon, defaultOpen = false, ...props }) {
    return <Accordion icon={lucideIcon(icon)} defaultOpen={defaultOpen} {...props} />;
}

/** The package hardcodes "Show"/"Hide" in English. */
function DocsExpandable({ openedText = 'Ocultar', closedText = 'Mostrar', ...props }) {
    return <Expandable openedText={openedText} closedText={closedText} {...props} />;
}

function DocsIcon({ icon, size = 20, ...props }) {
    return <DynamicIcon name={icon} size={size} {...props} />;
}

/** Pan and zoom controls overlap the drawing on short diagrams. */
function DocsMermaid({ actions = false, ...props }) {
    return <Mermaid actions={actions} {...props} />;
}

/**
 * Update returns null unless it gets an id and isVisible, neither of which an MDX author
 * can reasonably supply. The id is derived from the label so the anchor stays stable.
 */
function DocsUpdate({ label = '', id, ...props }) {
    const anchor =
        id ??
        label
            .toLowerCase()
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-|-$/g, '');

    return <Update id={anchor} label={label} isVisible {...props} />;
}

const PROPERTY_LABELS = {
    defaultLabel: 'Padrão',
    requiredLabel: 'obrigatório',
    deprecatedLabel: 'obsoleto',
};

function DocsParamField({ query, path, header, body, type = 'string', children, ...props }) {
    const name = query ?? path ?? header ?? body ?? props.name;
    const location = query ? 'query' : path ? 'path' : header ? 'header' : body ? 'body' : undefined;

    return (
        <Property name={name} type={type} location={location} {...PROPERTY_LABELS} {...props}>
            {children}
        </Property>
    );
}

function DocsResponseField({ name, type = 'string', children, ...props }) {
    return (
        <Property name={name} type={type} {...PROPERTY_LABELS} {...props}>
            {children}
        </Property>
    );
}

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
