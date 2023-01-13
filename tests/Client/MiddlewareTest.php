<?php

namespace Tomb1n0\GenericApiClient\Tests\Client;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Tomb1n0\GenericApiClient\Http\Client;
use Tomb1n0\GenericApiClient\Tests\BaseTestCase;
use Tomb1n0\GenericApiClient\Contracts\MiddlewareContract;

class AuthenticationMiddleware implements MiddlewareContract
{
    public function __construct(protected string $accessToken)
    {
    }

    public function handle(RequestInterface $request, callable $next): ResponseInterface
    {
        $request = $request->withHeader('Authorization', 'Bearer ' . $this->accessToken);

        return $next($request);
    }
}

class BeforeMiddleware implements MiddlewareContract
{
    public function handle(RequestInterface $request, callable $next): ResponseInterface
    {
        $request = $request->withHeader('X-Custom-Before-Header', 'Foo');

        return $next($request);
    }
}
class AfterMiddleware implements MiddlewareContract
{
    public function handle(RequestInterface $request, callable $next): ResponseInterface
    {
        $response = $next($request);

        $response = $response->withHeader('X-Custom-After-Header', 'Foo');

        return $response;
    }
}

class MiddlewareTest extends BaseTestCase
{
    /** @test */
    public function can_send_a_request_through_middleware()
    {
        $client = $this->createTestingClient(
            clientCreationCallback: function (Client $client) {
                return $client->withMiddleware([
                    new AuthenticationMiddleware('access-token'), // Adds an Authorization header
                    new AfterMiddleware(), // Adds a middleware that adds something to the response
                    new BeforeMiddleware(), // Adds a middleware that adds something to the request
                ]);
            },
        )->client;

        $client->stubResponse('https://example.com', ['id' => 1], 200, [
            'X-Original-Response-Header' => 'original-response-header',
        ]);

        $response = $client->json('GET', 'https://example.com');

        $psr7Request = $response->toPsr7Request();
        $psr7Response = $response->toPsr7Response();

        $this->assertSame('Bearer access-token', $psr7Request->getHeaderLine('Authorization'));
        $this->assertSame('Foo', $psr7Request->getHeaderLine('X-Custom-Before-Header'));
        $this->assertSame('Foo', $psr7Response->getHeaderLine('X-Custom-After-Header'));
    }
}
