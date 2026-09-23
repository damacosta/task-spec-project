import { Expandable, Property } from '@mintlify/components';
import InlineMarkdown from './InlineMarkdown';

const CONSTRAINT_LABELS = {
    minimum: 'Valor mínimo',
    maximum: 'Valor máximo',
    minLength: 'Tamanho mínimo',
    maxLength: 'Tamanho máximo',
    minItems: 'Mínimo de itens',
    maxItems: 'Máximo de itens',
    pattern: 'Padrão',
    format: 'Formato',
};

export default function FieldList({ fields, depth = 0 }) {
    if (!fields || fields.length === 0) return null;

    return (
        <div className="not-prose">
            {fields.map((field) => (
                <Field key={`${depth}-${field.name}`} field={field} depth={depth} />
            ))}
        </div>
    );
}

function Field({ field, depth }) {
    return (
        <Property
            name={field.name}
            type={field.type}
            required={field.required}
            deprecated={field.deprecated}
            requiredLabel="obrigatório"
            deprecatedLabel="obsoleto"
        >
            {field.description && (
                <InlineMarkdown text={field.description} className="text-sm text-stone-600 dark:text-stone-400" />
            )}

            <Meta field={field} />

            {field.children && (
                <div className="mt-3">
                    <Expandable title="propriedades" openedText="Ocultar" closedText="Mostrar">
                        <FieldList fields={field.children} depth={depth + 1} />
                    </Expandable>
                </div>
            )}
        </Property>
    );
}

function Meta({ field }) {
    const constraints = Object.entries(field.constraints ?? {});
    const hasExample = field.example !== null && field.example !== undefined;
    const hasDefault = field.default !== null && field.default !== undefined;

    if (!hasExample && !hasDefault && constraints.length === 0 && !field.enum) return null;

    return (
        <dl className="mt-2 flex flex-col gap-1 text-xs text-stone-500 dark:text-stone-400">
            {hasExample && <Entry label="Exemplo" value={format(field.example)} />}
            {hasDefault && <Entry label="Padrão" value={format(field.default)} />}

            {constraints.map(([key, value]) => (
                <Entry key={key} label={CONSTRAINT_LABELS[key] ?? key} value={String(value)} />
            ))}

            {field.enum && (
                <div className="mt-1">
                    <dt className="mb-1">Valores aceitos</dt>
                    <dd className="flex flex-col gap-1">
                        {field.enum.map((option) => (
                            <span key={String(option)} className="flex flex-wrap items-baseline gap-2">
                                <code className="rounded bg-stone-100 px-1 py-0.5 font-mono dark:bg-stone-800">
                                    {String(option)}
                                </code>
                                {field.enumDescriptions?.[option] && <span>{field.enumDescriptions[option]}</span>}
                            </span>
                        ))}
                    </dd>
                </div>
            )}
        </dl>
    );
}

function Entry({ label, value }) {
    return (
        <div className="flex flex-wrap items-baseline gap-2">
            <dt>{label}:</dt>
            <dd>
                <code className="rounded bg-stone-100 px-1 py-0.5 font-mono dark:bg-stone-800">{value}</code>
            </dd>
        </div>
    );
}

function format(value) {
    return typeof value === 'object' ? JSON.stringify(value) : String(value);
}
