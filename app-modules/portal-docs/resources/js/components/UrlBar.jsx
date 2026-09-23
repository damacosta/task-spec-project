import MethodBadge from './MethodBadge';

/** METHOD https://server/path, with the {param} segments picked out. */
export default function UrlBar({ method, server, path }) {
    return (
        <div className="not-prose flex flex-wrap items-center gap-2 rounded-xl border border-stone-200 bg-stone-50 px-3 py-2 font-mono text-sm dark:border-stone-800 dark:bg-stone-900/50">
            <MethodBadge method={method} size="md" />
            <span className="text-stone-500 dark:text-stone-400">{server}</span>
            <span className="break-all">
                {path.split(/(\{[^}]+\})/).map((segment, index) =>
                    segment.startsWith('{') ? (
                        <span key={index} className="text-primary">
                            {segment}
                        </span>
                    ) : (
                        <span key={index}>{segment}</span>
                    ),
                )}
            </span>
        </div>
    );
}
