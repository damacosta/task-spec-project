<?php

declare(strict_types=1);

use He4rt\PortalDocs\Http\Middleware\AuthorizeDocsAccess;
use He4rt\PortalDocs\Tests\Support\FixtureSite;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpKernel\Exception\HttpException;

use function Pest\Laravel\get;

/*
 * The middleware short-circuits on local and testing, so these call it directly with a
 * production environment rather than trying to flip the app environment mid-request.
 */

function runGate(): int
{
    $response = (new AuthorizeDocsAccess)->handle(
        Request::create('/docs/get-started'),
        fn (): Response => response('ok'),
    );

    return $response->getStatusCode();
}

beforeEach(fn () => FixtureSite::use());

it('lets every request through while testing', function (): void {
    get('/docs/get-started')->assertOk();
});

it('denies the portal when the gate says no', function (): void {
    app()->detectEnvironment(fn (): string => 'production');
    Gate::define('viewDocs', fn (): bool => false);

    expect(fn (): int => runGate())->toThrow(HttpException::class);
});

it('allows the portal when the gate says yes', function (): void {
    app()->detectEnvironment(fn (): string => 'production');
    Gate::define('viewDocs', fn (?Authenticatable $user): bool => true);

    expect(runGate())->toBe(200);
});

it('denies a guest when the gate does not accept one', function (): void {
    app()->detectEnvironment(fn (): string => 'production');
    Gate::define('viewDocs', fn (Authenticatable $user): bool => true);

    expect(fn (): int => runGate())->toThrow(HttpException::class);
});

it('opens the portal to everyone when no gate is configured', function (): void {
    app()->detectEnvironment(fn (): string => 'production');
    config()->set('portal-docs.gate', '');

    expect(runGate())->toBe(200);
});
