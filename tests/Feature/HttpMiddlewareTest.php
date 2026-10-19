<?php

declare(strict_types=1);

use GuzzleHttp\Promise\Create;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use XVE\ExactonlineLaravelApi\Models\ExactConnection;

/**
 * Apps can push Guzzle middleware onto every picqer connection
 * (exactonline-laravel-api.connection.http_middleware), so each HTTP call to
 * Exact passes through it: the way to count or audit Exact traffic exactly,
 * including the pages and token calls picqer makes on its own.
 */
function connectedExactConnection(): ExactConnection
{
    return ExactConnection::factory()->create([
        'is_active' => true,
        'division' => '123456',
        'base_url' => 'https://start.exactonline.test',
        'access_token' => 'access-token',
        'refresh_token' => 'refresh-token',
        'token_expires_at' => now()->addMinutes(10)->timestamp,
    ]);
}

/**
 * A middleware that records each request and answers it itself, so the test
 * never reaches the network.
 *
 * @param  list<string>  $seen
 */
function recordingMiddleware(array &$seen): callable
{
    return function (callable $handler) use (&$seen): callable {
        return function (RequestInterface $request, array $options) use (&$seen) {
            $seen[] = $request->getMethod().' '.$request->getUri()->getPath();

            return Create::promiseFor(new Response(
                200,
                ['Content-Type' => 'application/json'],
                // Two rows: picqer unwraps a single-row result to the row itself.
                (string) json_encode(['d' => ['results' => [['ID' => 'account-1'], ['ID' => 'account-2']]]]),
            ));
        };
    };
}

it('runs every configured middleware on the calls a picqer connection makes', function () {
    $seen = [];
    config()->set('exactonline-laravel-api.connection.http_middleware', [recordingMiddleware($seen)]);

    $rows = connectedExactConnection()->getPicqerConnection()->get('crm/Accounts');

    expect($seen)->toBe(['GET /api/v1/123456/crm/Accounts'])
        ->and(array_column($rows, 'ID'))->toBe(['account-1', 'account-2']);
});

it('resolves an invokable middleware class from the container', function () {
    $middleware = new class
    {
        public int $calls = 0;

        public function __invoke(callable $handler): callable
        {
            return function (RequestInterface $request, array $options) {
                $this->calls++;

                return Create::promiseFor(new Response(200, [], (string) json_encode(['d' => ['results' => []]])));
            };
        }
    };
    app()->instance('test.exact-counter', $middleware);
    app()->bind($middleware::class, fn () => app('test.exact-counter'));
    config()->set('exactonline-laravel-api.connection.http_middleware', [$middleware::class]);

    connectedExactConnection()->getPicqerConnection()->get('crm/Accounts');

    expect($middleware->calls)->toBe(1);
});

it('adds nothing when no middleware is configured', function () {
    config()->set('exactonline-laravel-api.connection.http_middleware', []);

    $picqer = connectedExactConnection()->getPicqerConnection();

    $property = (new ReflectionClass($picqer))->getProperty('middleWares');
    $property->setAccessible(true);

    expect($property->getValue($picqer))->toBe([]);
});

it('refuses a middleware entry that is not callable', function () {
    config()->set('exactonline-laravel-api.connection.http_middleware', ['not-a-class']);

    connectedExactConnection()->getPicqerConnection();
})->throws(InvalidArgumentException::class);
