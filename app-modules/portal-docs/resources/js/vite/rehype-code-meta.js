import { visit } from 'unist-util-visit';

const FLAGS = new Set(['lines', 'wrap', 'expandable', 'nocopy', 'focus']);

/**
 * Moves the text after a fence's language onto the <pre> element as props.
 *
 * ```bash Terminal lines wrap
 * becomes <pre language="bash" filename="Terminal" lines wrap>, which is what turns a
 * plain fence into a titled CodeBlock and a set of fences into CodeGroup tabs. Without
 * this the meta string is parsed by the MDX compiler and then thrown away.
 */
export default function rehypeCodeMeta() {
    return (tree) => {
        visit(tree, 'element', (node) => {
            if (node.tagName !== 'pre') return;

            const code = node.children?.find((child) => child.tagName === 'code');
            if (!code) return;

            const language = (code.properties?.className ?? [])
                .find((name) => typeof name === 'string' && name.startsWith('language-'))
                ?.slice('language-'.length);

            if (language) node.properties.language = language;

            const meta = code.data?.meta;
            if (typeof meta !== 'string' || meta.trim() === '') return;

            const filename = [];

            for (const token of meta.match(/(?:[^\s"]+|"[^"]*")+/g) ?? []) {
                const [key, ...rest] = token.split('=');

                if (rest.length > 0) {
                    node.properties[key] = rest.join('=').replace(/^"|"$/g, '');
                } else if (FLAGS.has(token)) {
                    node.properties[token] = true;
                } else {
                    filename.push(token.replace(/^"|"$/g, ''));
                }
            }

            if (filename.length > 0) node.properties.filename = filename.join(' ');
        });
    };
}
