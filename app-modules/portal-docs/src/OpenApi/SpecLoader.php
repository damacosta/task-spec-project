<?php

declare(strict_types=1);

namespace He4rt\PortalDocs\OpenApi;

interface SpecLoader
{
    /**
     * @return array<string, mixed> an OpenAPI 3.x document, already resolved by the generator
     */
    public function load(): array;
}
