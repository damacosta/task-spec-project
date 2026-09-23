<?php

declare(strict_types=1);

namespace He4rt\PortalDocs\OpenApi;

/**
 * Serves a spec held in memory. Tests bind this so the suite never runs Scramble.
 */
final readonly class ArraySpecLoader implements SpecLoader
{
    /**
     * @param  array<string, mixed>  $spec
     */
    public function __construct(private array $spec) {}

    public function load(): array
    {
        return $this->spec;
    }
}
