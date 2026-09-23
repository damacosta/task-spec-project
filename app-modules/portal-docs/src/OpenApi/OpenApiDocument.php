<?php

declare(strict_types=1);

namespace He4rt\PortalDocs\OpenApi;

use He4rt\PortalDocs\Support\Value;
use Illuminate\Support\Str;

/**
 * Reads one generated OpenAPI document and answers what the portal needs:
 * the endpoint groups for the sidebar, the props of one endpoint page, and its markdown.
 */
final class OpenApiDocument
{
    private const array METHODS = ['get', 'post', 'put', 'patch', 'delete', 'head', 'options'];

    private readonly JsonSchema $schema;

    /** @var array<string, array<string, mixed>>|null */
    private ?array $operations = null;

    /**
     * @param  array<string, mixed>  $spec
     */
    public function __construct(private readonly array $spec)
    {
        $this->schema = new JsonSchema($spec);
    }

    /**
     * Slug of one operation. The separators are split out first: without that,
     * "delete /posts/{post}" collapses into "delete-postspost".
     */
    public static function slugFor(string $tag, string $method, string $path): string
    {
        return 'api/'.Str::slug($tag).'/'.Str::slug(str_replace(['/', '{', '}'], ' ', $method.' '.$path));
    }

    /**
     * One group per tag, in the order the spec declares them.
     *
     * @return list<array{group: string, pages: list<array{slug: string, title: string, method: string, deprecated: bool}>}>
     */
    public function groups(): array
    {
        $groups = [];

        foreach ($this->allOperations() as $operation) {
            $groups[$operation['tag']][] = [
                'slug' => $operation['slug'],
                'title' => $operation['title'],
                'method' => $operation['method'],
                'deprecated' => $operation['deprecated'],
            ];
        }

        $resolved = [];

        foreach ($groups as $tag => $pages) {
            $resolved[] = ['group' => (string) $tag, 'pages' => $pages];
        }

        return $resolved;
    }

    public function has(string $slug): bool
    {
        return array_key_exists($slug, $this->allOperations());
    }

    /**
     * Everything the Docs/Endpoint page renders.
     *
     * @return array<string, mixed>|null
     */
    public function endpoint(string $slug): ?array
    {
        $operation = $this->allOperations()[$slug] ?? null;

        if ($operation === null) {
            return null;
        }

        /** @var array<string, mixed> $raw */
        $raw = $operation['raw'];
        $method = $operation['method'];
        $path = $operation['path'];
        $server = $this->server();

        $parameters = $this->parameters($raw);
        $security = $this->security($raw);
        $body = $this->body($raw);
        $responses = $this->responses($raw);

        return [
            'slug' => $slug,
            'title' => $operation['title'],
            'description' => is_string($raw['description'] ?? null) ? $raw['description'] : null,
            'deprecated' => $operation['deprecated'],
            'method' => mb_strtoupper($method),
            'path' => $path,
            'server' => $server,
            'url' => mb_rtrim($server, '/').$path,
            'tag' => $operation['tag'],
            'security' => $security,
            'parameters' => $parameters,
            'body' => $body,
            'responses' => $responses,
            'requestExamples' => RequestExamples::build(
                $method,
                mb_rtrim($server, '/').$path,
                $parameters['header'],
                $security,
                $body['example'] ?? null,
                $body['contentType'] ?? null,
            ),
        ];
    }

    /**
     * The same page as plain markdown, for `{slug}.md` and llms-full.txt.
     */
    public function markdown(string $slug): ?string
    {
        $endpoint = $this->endpoint($slug);

        if ($endpoint === null) {
            return null;
        }

        $lines = ['# '.$endpoint['title'], '', '> '.$endpoint['method'].' '.$endpoint['url'], ''];

        if (is_string($endpoint['description']) && $endpoint['description'] !== '') {
            $lines[] = $endpoint['description'];
            $lines[] = '';
        }

        if ($endpoint['security'] !== []) {
            $lines[] = '## Authorizations';
            $lines[] = '';

            foreach ($endpoint['security'] as $scheme) {
                $lines[] = '- `'.$scheme['key'].'` — '.($scheme['description'] ?? $scheme['type']);
            }

            $lines[] = '';
        }

        foreach (['path' => 'Path parameters', 'query' => 'Query parameters', 'header' => 'Headers'] as $key => $heading) {
            if ($endpoint['parameters'][$key] === []) {
                continue;
            }

            $lines[] = '## '.$heading;
            $lines[] = '';
            $lines = [...$lines, ...self::fieldLines(Value::maps($endpoint['parameters'][$key])), ''];
        }

        if (is_array($endpoint['body'])) {
            $lines[] = '## Body ('.$endpoint['body']['contentType'].')';
            $lines[] = '';
            $lines = [...$lines, ...self::fieldLines(Value::maps($endpoint['body']['fields'])), ''];
        }

        foreach ($endpoint['responses'] as $response) {
            $lines[] = '## Response '.$response['status'];
            $lines[] = '';

            if (is_string($response['description']) && $response['description'] !== '') {
                $lines[] = $response['description'];
                $lines[] = '';
            }

            if ($response['fields'] !== []) {
                $lines = [...$lines, ...self::fieldLines(Value::maps($response['fields'])), ''];
            }
        }

        $lines[] = '## Request example';
        $lines[] = '';
        $lines[] = '```bash';
        $lines[] = $endpoint['requestExamples']['curl'];
        $lines[] = '```';

        return implode("\n", $lines)."\n";
    }

    public function server(): string
    {
        $servers = is_array($this->spec['servers'] ?? null) ? $this->spec['servers'] : [];
        $first = $servers[0] ?? null;

        if (is_array($first) && is_string($first['url'] ?? null)) {
            return $first['url'];
        }

        return mb_rtrim((string) config()->string('app.url'), '/');
    }

    /**
     * @param  list<array<string, mixed>>  $fields
     * @return list<string>
     */
    private static function fieldLines(array $fields, int $indent = 0): array
    {
        $lines = [];
        $pad = str_repeat('  ', $indent);

        foreach ($fields as $field) {
            $flags = [$field['type']];

            if ($field['required'] === true) {
                $flags[] = 'required';
            }

            if ($field['deprecated'] === true) {
                $flags[] = 'deprecated';
            }

            $line = $pad.'- `'.$field['name'].'` ('.implode(', ', $flags).')';

            if (is_string($field['description']) && $field['description'] !== '') {
                $line .= ': '.$field['description'];
            }

            $lines[] = $line;

            if (is_array($field['children'])) {
                $lines = [...$lines, ...self::fieldLines(Value::maps($field['children']), $indent + 1)];
            }
        }

        return $lines;
    }

    /**
     * @param  array<string, mixed>  $operation
     * @return array{path: list<array<string, mixed>>, query: list<array<string, mixed>>, header: list<array<string, mixed>>}
     */
    private function parameters(array $operation): array
    {
        $grouped = ['path' => [], 'query' => [], 'header' => []];
        $parameters = is_array($operation['parameters'] ?? null) ? $operation['parameters'] : [];

        foreach ($parameters as $parameter) {
            if (!is_array($parameter)) {
                continue;
            }

            $parameter = $this->schema->resolve($parameter);
            $in = $parameter['in'] ?? null;
            $name = $parameter['name'] ?? null;

            if (!is_string($in) || !is_string($name) || !array_key_exists($in, $grouped)) {
                continue;
            }

            $schema = Value::map($parameter['schema'] ?? null);
            $field = $this->schema->field($name, $schema, (bool) ($parameter['required'] ?? false));

            if (is_string($parameter['description'] ?? null) && $parameter['description'] !== '') {
                $field['description'] = $parameter['description'];
            }

            if (array_key_exists('example', $parameter)) {
                $field['example'] = $parameter['example'];
            }

            if (($parameter['deprecated'] ?? false) === true) {
                $field['deprecated'] = true;
            }

            $grouped[$in][] = $field;
        }

        return $grouped;
    }

    /**
     * @param  array<string, mixed>  $operation
     * @return array{contentType: string, required: bool, fields: list<array<string, mixed>>, example: mixed}|null
     */
    private function body(array $operation): ?array
    {
        $requestBody = $operation['requestBody'] ?? null;

        if (!is_array($requestBody)) {
            return null;
        }

        $requestBody = $this->schema->resolve($requestBody);
        $content = is_array($requestBody['content'] ?? null) ? $requestBody['content'] : [];

        if ($content === []) {
            return null;
        }

        $contentType = (string) array_key_first($content);
        $media = Value::map($content[$contentType] ?? null);
        $schema = Value::map($media['schema'] ?? null);

        return [
            'contentType' => $contentType,
            'required' => (bool) ($requestBody['required'] ?? false),
            'fields' => $this->schema->fields($schema),
            'example' => $media['example'] ?? $this->schema->example($schema),
        ];
    }

    /**
     * @param  array<string, mixed>  $operation
     * @return list<array{status: string, description: string|null, contentType: string|null, fields: list<array<string, mixed>>, example: mixed}>
     */
    private function responses(array $operation): array
    {
        $responses = is_array($operation['responses'] ?? null) ? $operation['responses'] : [];
        $resolved = [];

        foreach ($responses as $status => $response) {
            if (!is_array($response)) {
                continue;
            }

            $response = $this->schema->resolve($response);
            $content = is_array($response['content'] ?? null) ? $response['content'] : [];
            $contentType = $content === [] ? null : (string) array_key_first($content);
            $media = $contentType === null ? [] : Value::map($content[$contentType]);
            $schema = Value::map($media['schema'] ?? null);

            $resolved[] = [
                'status' => (string) $status,
                'description' => is_string($response['description'] ?? null) ? $response['description'] : null,
                'contentType' => $contentType,
                'fields' => $schema === [] ? [] : $this->schema->fields($schema),
                'example' => $schema === [] ? null : ($media['example'] ?? $this->schema->example($schema)),
            ];
        }

        return $resolved;
    }

    /**
     * @param  array<string, mixed>  $operation
     * @return list<array{key: string, name: string|null, type: string, in: string|null, scheme: string|null, description: string|null}>
     */
    private function security(array $operation): array
    {
        $components = is_array($this->spec['components'] ?? null) ? $this->spec['components'] : [];
        $schemes = is_array($components['securitySchemes'] ?? null) ? $components['securitySchemes'] : [];

        $requirements = $operation['security'] ?? $this->spec['security'] ?? [];
        $requirements = is_array($requirements) ? $requirements : [];

        $resolved = [];

        foreach ($requirements as $requirement) {
            if (!is_array($requirement)) {
                continue;
            }

            foreach (array_keys($requirement) as $name) {
                $scheme = is_array($schemes[$name] ?? null) ? $schemes[$name] : null;

                if ($scheme === null) {
                    continue;
                }

                $resolved[] = [
                    'key' => (string) $name,
                    // For an apiKey scheme this is the header name; the scheme key above is not.
                    'name' => is_string($scheme['name'] ?? null) ? $scheme['name'] : null,
                    'type' => is_string($scheme['type'] ?? null) ? $scheme['type'] : 'http',
                    'in' => is_string($scheme['in'] ?? null) ? $scheme['in'] : null,
                    'scheme' => is_string($scheme['scheme'] ?? null) ? $scheme['scheme'] : null,
                    'description' => is_string($scheme['description'] ?? null) ? $scheme['description'] : null,
                ];
            }
        }

        return $resolved;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function allOperations(): array
    {
        if ($this->operations !== null) {
            return $this->operations;
        }

        $paths = is_array($this->spec['paths'] ?? null) ? $this->spec['paths'] : [];
        $operations = [];

        foreach ($paths as $path => $methods) {
            if (!is_string($path) || !is_array($methods)) {
                continue;
            }

            foreach ($methods as $method => $operation) {
                if (!is_string($method) || !is_array($operation) || !in_array(mb_strtolower($method), self::METHODS, strict: true)) {
                    continue;
                }

                $tags = is_array($operation['tags'] ?? null) ? $operation['tags'] : [];
                $tag = is_string($tags[0] ?? null) ? $tags[0] : 'Endpoints';
                $slug = self::slugFor($tag, $method, $path);

                $operations[$slug] = [
                    'slug' => $slug,
                    'tag' => $tag,
                    'method' => mb_strtolower($method),
                    'path' => $path,
                    'deprecated' => (bool) ($operation['deprecated'] ?? false),
                    'title' => EndpointTitle::make(
                        is_string($operation['summary'] ?? null) ? $operation['summary'] : null,
                        is_string($operation['operationId'] ?? null) ? $operation['operationId'] : null,
                        $method,
                        $path,
                        $tag,
                    ),
                    'raw' => Value::map($operation),
                ];
            }
        }

        return $this->operations = $operations;
    }
}
