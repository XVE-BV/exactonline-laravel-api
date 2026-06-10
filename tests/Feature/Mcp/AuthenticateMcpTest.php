<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use XVE\ExactonlineLaravelApi\Http\Middleware\AuthenticateMcp;

function handleMcpRequestWithToken(?string $configuredToken, ?string $providedToken = null, bool $asBearer = false): Response
{
    config()->set('exactonline-laravel-api.mcp.auth.token', $configuredToken);
    config()->set('exactonline-laravel-api.mcp.auth.header', 'X-MCP-Token');

    $request = Request::create('/exact/mcp', 'POST');

    if ($providedToken !== null) {
        if ($asBearer) {
            $request->headers->set('Authorization', 'Bearer '.$providedToken);
        } else {
            $request->headers->set('X-MCP-Token', $providedToken);
        }
    }

    return (new AuthenticateMcp)->handle($request, fn () => response('passed', 200));
}

it('allows the configured token from the configured header', function () {
    $response = handleMcpRequestWithToken('secret-token', 'secret-token');

    expect($response->getStatusCode())->toBe(200)
        ->and($response->getContent())->toBe('passed');
});

it('allows the configured token from the authorization bearer header', function () {
    $response = handleMcpRequestWithToken('secret-token', 'secret-token', asBearer: true);

    expect($response->getStatusCode())->toBe(200)
        ->and($response->getContent())->toBe('passed');
});

it('rejects the wrong token', function () {
    $response = handleMcpRequestWithToken('secret-token', 'wrong');

    expect($response->getStatusCode())->toBe(401);
});

it('rejects a missing token', function () {
    $response = handleMcpRequestWithToken('secret-token');

    expect($response->getStatusCode())->toBe(401);
});

it('fails closed when the configured token is null', function () {
    $response = handleMcpRequestWithToken(null, 'secret-token');

    expect($response->getStatusCode())->toBe(401);
});
