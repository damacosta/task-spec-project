<?php

declare(strict_types=1);

namespace He4rt\PortalDocs\OpenApi;

use Dedoc\Scramble\CacheableGenerator;
use Dedoc\Scramble\Scramble;

/**
 * Scramble is used as an OpenAPI array generator only; its own UI is never registered.
 *
 * CacheableGenerator only READS the cache. `scramble:cache` is what writes it, so without
 * that command every call re-runs static analysis over the controllers.
 */
final readonly class ScrambleSpecLoader implements SpecLoader
{
    public function __construct(private string $api = Scramble::DEFAULT_API) {}

    public function load(): array
    {
        /** @var array<string, mixed> $spec */
        $spec = resolve(CacheableGenerator::class)(Scramble::getGeneratorConfig($this->api));

        return $spec;
    }
}
