<?php

declare(strict_types=1);

namespace He4rt\PortalDocs\Docs;

use Closure;
use Illuminate\Support\Facades\Cache;

/**
 * Wraps the navigation and the resolved endpoints in the application cache.
 *
 * The key follows content mtimes in development, so editing an .mdx shows up without a
 * cache:clear. Set portal-docs.cache.version (a deploy hash) in production and no request
 * has to stat the content directory.
 */
final readonly class NavigationCache
{
    public function __construct(
        private bool $enabled,
        private int $ttl,
        private ?string $version,
    ) {}

    public static function fromConfig(): self
    {
        return new self(
            enabled: config()->boolean('portal-docs.cache.enabled', default: true),
            ttl: config()->integer('portal-docs.cache.ttl', 86_400),
            version: config()->string('portal-docs.cache.version') ?: null,
        );
    }

    /**
     * @template TValue
     *
     * @param  Closure(): TValue  $callback
     * @return TValue
     */
    public function remember(string $name, DocsSite $site, Closure $callback): mixed
    {
        if (!$this->enabled) {
            return $callback();
        }

        return Cache::remember($this->key($name, $site), $this->ttl, $callback);
    }

    public function forget(string $name, DocsSite $site): void
    {
        Cache::forget($this->key($name, $site));
    }

    public function key(string $name, DocsSite $site): string
    {
        return 'portal-docs:'.$name.':'.($this->version ?? $site->fingerprint());
    }
}
