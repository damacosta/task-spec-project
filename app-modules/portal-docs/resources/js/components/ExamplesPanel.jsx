import { CodeBlock, CodeGroup } from '@mintlify/components';

const LANGUAGES = [
    { key: 'curl', label: 'cURL', language: 'bash' },
    { key: 'php', label: 'PHP', language: 'php' },
    { key: 'javascript', label: 'JavaScript', language: 'javascript' },
];

/**
 * Static snippets and responses. Nothing here is executed; there is no "Try it" yet.
 *
 * Every block wraps: the panel is 26rem wide and a curl line or a JSON body would
 * otherwise scroll sideways, hiding the part that matters.
 */
export default function ExamplesPanel({ examples, responses }) {
    return (
        <div className="flex flex-col gap-6">
            <CodeGroup dropdown>
                {LANGUAGES.filter(({ key }) => examples?.[key]).map(({ key, label, language }) => (
                    <CodeBlock key={key} filename={label} language={language} wrap>
                        <code className={`language-${language}`}>{examples[key]}</code>
                    </CodeBlock>
                ))}
            </CodeGroup>

            {responses?.length > 0 && (
                <CodeGroup>
                    {responses.map((response) => (
                        <CodeBlock key={response.status} filename={String(response.status)} language="json" wrap>
                            <code className="language-json">
                                {response.example === null || response.example === undefined
                                    ? '// sem corpo'
                                    : JSON.stringify(response.example, null, 2)}
                            </code>
                        </CodeBlock>
                    ))}
                </CodeGroup>
            )}
        </div>
    );
}
