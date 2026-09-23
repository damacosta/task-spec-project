import mdx from '@mdx-js/rollup';
import rehypeSlug from 'rehype-slug';
import remarkFrontmatter from 'remark-frontmatter';
import remarkGfm from 'remark-gfm';
import remarkMdxFrontmatter from 'remark-mdx-frontmatter';
import rehypeCodeMeta from './rehype-code-meta.js';

/**
 * The MDX plugin has to run with enforce: 'pre' and before @vitejs/plugin-react, or the
 * React plugin sees .mdx as plain JavaScript and the build fails on the first component.
 */
export function portalDocsMdx() {
    return {
        enforce: 'pre',
        ...mdx({
            providerImportSource: '@mdx-js/react',
            remarkPlugins: [remarkFrontmatter, remarkMdxFrontmatter, remarkGfm],
            rehypePlugins: [rehypeSlug, rehypeCodeMeta],
        }),
    };
}
