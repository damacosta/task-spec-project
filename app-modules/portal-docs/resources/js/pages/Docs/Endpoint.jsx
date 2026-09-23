import { Head, usePage } from '@inertiajs/react';
import ExamplesPanel from '../../components/ExamplesPanel';
import FieldList from '../../components/FieldList';
import InlineMarkdown from '../../components/InlineMarkdown';
import Pagination from '../../components/Pagination';
import UrlBar from '../../components/UrlBar';
import DocsLayout from '../../DocsLayout';

const PARAMETER_SECTIONS = [
    ['path', 'Parâmetros de caminho'],
    ['query', 'Parâmetros de consulta'],
    ['header', 'Cabeçalhos'],
];

export default function Endpoint() {
    const { config, page, endpoint, previous, next } = usePage().props;

    return (
        <>
            <Head title={`${page.title} - ${config.name}`} />

            <main className="min-w-0 flex-1 py-10">
                <article className="prose prose-stone max-w-3xl dark:prose-invert">
                    <p className="not-prose text-primary mb-2 text-sm font-medium">{endpoint.tag}</p>
                    <h1 className="mb-2">{page.title}</h1>

                    {endpoint.deprecated && (
                        <p className="not-prose mb-4 inline-block rounded-lg bg-amber-50 px-3 py-1 text-sm text-amber-700 dark:bg-amber-400/10 dark:text-amber-300">
                            Este endpoint está obsoleto.
                        </p>
                    )}

                    <UrlBar method={endpoint.method} server={endpoint.server} path={endpoint.path} />

                    {endpoint.description && (
                        <InlineMarkdown
                            text={endpoint.description}
                            className="not-prose mt-6 text-stone-600 dark:text-stone-400"
                        />
                    )}

                    {endpoint.security.length > 0 && (
                        <Section title="Autorizações">
                            <FieldList
                                fields={endpoint.security.map((scheme) => ({
                                    name: scheme.name ?? 'Authorization',
                                    type:
                                        scheme.type === 'apiKey'
                                            ? 'apiKey'
                                            : `${scheme.type} ${scheme.scheme ?? ''}`.trim(),
                                    required: true,
                                    deprecated: false,
                                    description: scheme.description,
                                    constraints: {},
                                    children: null,
                                }))}
                            />
                        </Section>
                    )}

                    {PARAMETER_SECTIONS.map(([key, title]) =>
                        endpoint.parameters[key].length > 0 ? (
                            <Section key={key} title={title}>
                                <FieldList fields={endpoint.parameters[key]} />
                            </Section>
                        ) : null,
                    )}

                    {endpoint.body && (
                        <Section title={`Corpo (${endpoint.body.contentType})`}>
                            <FieldList fields={endpoint.body.fields} />
                        </Section>
                    )}

                    {endpoint.responses.map((response) => (
                        <Section key={response.status} title={`Resposta ${response.status}`}>
                            {response.description && (
                                <InlineMarkdown
                                    text={response.description}
                                    className="not-prose mb-4 text-sm text-stone-600 dark:text-stone-400"
                                />
                            )}
                            <FieldList fields={response.fields} />
                        </Section>
                    ))}
                </article>

                <div className="max-w-3xl">
                    <Pagination previous={previous} next={next} />
                </div>
            </main>

            <aside className="sticky top-28 hidden h-[calc(100vh-7rem)] w-[26rem] shrink-0 overflow-y-auto py-10 xl:block">
                <ExamplesPanel examples={endpoint.requestExamples} responses={endpoint.responses} />
            </aside>
        </>
    );
}

function Section({ title, children }) {
    return (
        <section className="mt-8">
            <h2 className="mb-3 text-base">{title}</h2>
            {children}
        </section>
    );
}

Endpoint.layout = (page) => <DocsLayout>{page}</DocsLayout>;
