const PROMPT = (url) => `Read from ${url}.md so I can ask questions about it.`;

/**
 * The entries of the "Copy page" dropdown, filtered by what docs.json allows.
 * `copy` is handled by the component itself and carries no href.
 */
export function contextualOptions(allowed, pageUrl, markdownUrl) {
    const all = {
        copy: { id: 'copy', label: 'Copiar página', description: 'Copia esta página como Markdown' },
        view: { id: 'view', label: 'Ver como Markdown', href: markdownUrl, external: true },
        chatgpt: {
            id: 'chatgpt',
            label: 'Abrir no ChatGPT',
            href: `https://chat.openai.com/?q=${encodeURIComponent(PROMPT(pageUrl))}`,
            external: true,
        },
        claude: {
            id: 'claude',
            label: 'Abrir no Claude',
            href: `https://claude.ai/new?q=${encodeURIComponent(PROMPT(pageUrl))}`,
            external: true,
        },
    };

    return allowed.map((id) => all[id]).filter(Boolean);
}
