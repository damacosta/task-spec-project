<?php

declare(strict_types=1);

namespace He4rt\PortalDocs\OpenApi\Scramble;

use Dedoc\Scramble\Contracts\DocumentTransformer;
use Dedoc\Scramble\OpenApiContext;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\SecuritySchemes\HttpSecurityScheme;
use Illuminate\Support\Str;

/**
 * Conventions static analysis cannot infer: how the API is authenticated, and what the
 * shared error responses mean.
 *
 * Documenting a scheme here does NOT enforce it. The routes carry their own middleware;
 * keep the two in sync by hand.
 */
final class DocumentsApiConventions implements DocumentTransformer
{
    public function handle(OpenApi $document, OpenApiContext $context): void
    {
        $bearer = new HttpSecurityScheme('bearer');
        $bearer->setDescription('A personal access token sent as `Authorization: Bearer <token>`.');
        $bearer->as('bearerAuth');

        $document->secure($bearer);

        $this->describeSharedResponses($context);
    }

    private function describeSharedResponses(OpenApiContext $context): void
    {
        $descriptions = [
            'ValidationException' => 'The payload failed validation. `errors` holds one list of messages per field.',
            'ModelNotFoundException' => 'No record matches the identifier in the path.',
            'AuthenticationException' => 'The request carries no valid credentials.',
            'AuthorizationException' => 'The credentials are valid but lack the required ability.',
        ];

        // Components key responses by FQCN; the short name only appears once serialised.
        foreach ($context->openApi->components->responses as $name => $response) {
            $shortName = Str::afterLast($name, '\\');

            if (isset($descriptions[$shortName])) {
                $response->description($descriptions[$shortName]);
            }
        }
    }
}
