<?php

namespace Tomb1n0\GenericApiClient\Tests;

use Closure;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use GuzzleHttp\Psr7\HttpFactory;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Tomb1n0\GenericApiClient\Tests\Mocks\ClientMock;

class BaseTestCase extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
    }

    /**
     * Mock the given class, passing it to the given closure
     *
     * @template T of object
     * @param class-string<T> $classToMock
     * @param Closure $callback
     * @return MockInterface|T
     */
    protected function mock(string $classToMock, ?Closure $callback = null)
    {
        $mock = Mockery::mock($classToMock);

        if ($callback) {
            $callback($mock);
        }

        return $mock;
    }

    protected function httpVerbsProvider()
    {
        return [['GET'], ['POST'], ['PUT'], ['PATCH'], ['DELETE']];
    }

    protected function httpVerbsExcludingGetProvider()
    {
        return [['POST'], ['PUT'], ['PATCH'], ['DELETE']];
    }

    protected function bodyTypesProvider()
    {
        $resourceBody = 'Resource Body!';
        $resource = fopen('php://memory', 'r+');
        fwrite($resource, $resourceBody);
        rewind($resource);

        return [
            [$resource, $resourceBody],
            ['body', 'body'],
            [['id' => 1], json_encode(['id' => 1])],
            [http_build_query(['id' => 1]), http_build_query(['id' => 1])],
            [1, '1'],
            [0, '0'],
            [null, ''],
            [true, '1'],
            [false, ''],
        ];
    }

    protected function requestFactory(): RequestFactoryInterface
    {
        return new HttpFactory();
    }

    protected function responseFactory(): ResponseFactoryInterface
    {
        return new HttpFactory();
    }

    protected function streamFactory(): StreamFactoryInterface
    {
        return new HttpFactory();
    }

    protected function uriFactory(): UriFactoryInterface
    {
        return new HttpFactory();
    }

    /**
     * Mock the SendRequest call and defer to the given callback to assert it is what we expected.
     *
     * Finally return the Response that we returned from sendRequest.
     *
     * @param ClientMock $clientMock
     * @param Closure $callback
     * @return void
     */
    protected function expectSendRequest(ClientMock $clientMock, ?Closure $callback = null): ResponseInterface
    {
        $response = $this->responseFactory()->createResponse(200, 'OK');

        $clientMock->psr18Client
            ->shouldReceive('sendRequest')
            ->once()
            ->with(
                Mockery::on(function (RequestInterface $request) use ($callback) {
                    return $callback ? $callback($request) : true;
                }),
            )
            ->andReturn($response);

        return $response;
    }

    /**
     * Return a new Client Mock object.
     *
     * @return ClientMock
     */
    protected function createTestingClient(
        ?Closure $clientCreationCallback = null,
        null|MockInterface|ClientInterface $psr18Client = null,
        null|MockInterface|RequestFactoryInterface $psr17RequestFactory = null,
        null|MockInterface|ResponseFactoryInterface $psr17ResponseFactory = null,
        null|MockInterface|StreamFactoryInterface $psr7StreamFactory = null,
        null|MockInterface|UriFactoryInterface $psr7UriFactory = null,
    ): ClientMock {
        return new ClientMock(
            $psr18Client ?? $this->mock(ClientInterface::class),
            $psr17RequestFactory ?? $this->requestFactory(),
            $psr17ResponseFactory ?? $this->responseFactory(),
            $psr7StreamFactory ?? $this->streamFactory(),
            $psr7UriFactory ?? $this->uriFactory(),
            $clientCreationCallback,
        );
    }
}
