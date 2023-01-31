<?php

namespace Tomb1n0\GenericApiClient\Tests\Client;

use Mockery;
use Psr\Http\Message\UriInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Tomb1n0\GenericApiClient\Tests\BaseTestCase;
use Tomb1n0\GenericApiClient\Contracts\MiddlewareContract;
use Tomb1n0\GenericApiClient\Contracts\PaginationHandlerContract;

class ClientTest extends BaseTestCase
{
    /** @test */
    public function can_create_with_a_base_url()
    {
        $baseUrl = 'https://example.com';

        $client = $this->createTestingClient()->client->withBaseUrl($baseUrl);

        $this->assertSame($baseUrl, $client->getBaseUrl());
    }

    /** @test */
    public function can_create_with_a_pagination_handler()
    {
        $handler = $this->mock(PaginationHandlerContract::class);

        $client = $this->createTestingClient()->client->withPaginationHandler($handler);

        $this->assertSame($handler, $client->getPaginationHandler());
    }

    /** @test */
    public function can_create_with_middleware()
    {
        $middleware = [$this->mock(MiddlewareContract::class), $this->mock(MiddlewareContract::class)];

        $client = $this->createTestingClient()->client->withMiddleware($middleware);

        $this->assertSame(array_reverse($middleware), $client->getMiddleware());
    }

    /** @test */
    public function creating_with_base_url_pagination_handler_etc_doesnt_lose_values()
    {
        $baseUrl = 'https://example.com';
        $middleware = [$this->mock(MiddlewareContract::class), $this->mock(MiddlewareContract::class)];
        $handler = $this->mock(PaginationHandlerContract::class);

        $client = $this->createTestingClient()
            ->client->withPaginationHandler($handler)
            ->withMiddleware($middleware)
            ->withBaseUrl($baseUrl);

        $this->assertSame($handler, $client->getPaginationHandler());
        $this->assertSame(array_reverse($middleware), $client->getMiddleware());
        $this->assertSame($baseUrl, $client->getBaseUrl());
    }

    /** @test */
    public function it_will_use_the_provided_psr18_client_to_make_json_requests()
    {
        $psr7Response = $this->responseFactory()->createResponse(200);
        $psr18Client = $this->mock(ClientInterface::class, function ($mock) use ($psr7Response) {
            $mock
                ->shouldReceive('sendRequest')
                ->once()
                ->andReturn($psr7Response);
        });

        $testingClient = $this->createTestingClient(psr18Client: $psr18Client);

        $response = $testingClient->client->json('GET', 'https://example.com');

        $this->assertSame($psr7Response, $response->toPsr7Response());
    }

    /** @test */
    public function it_will_use_the_provided_psr18_client_to_make_form_requests()
    {
        $psr7Response = $this->responseFactory()->createResponse(200);
        $psr18Client = $this->mock(ClientInterface::class, function ($mock) use ($psr7Response) {
            $mock
                ->shouldReceive('sendRequest')
                ->once()
                ->andReturn($psr7Response);
        });

        $testingClient = $this->createTestingClient(psr18Client: $psr18Client);

        $response = $testingClient->client->form('GET', 'https://example.com');

        $this->assertSame($psr7Response, $response->toPsr7Response());
    }

    /** @test */
    public function it_will_use_the_provided_psr18_client_to_make_custom_requests()
    {
        $psr7Response = $this->responseFactory()->createResponse(200);
        $psr18Client = $this->mock(ClientInterface::class, function ($mock) use ($psr7Response) {
            $mock
                ->shouldReceive('sendRequest')
                ->once()
                ->andReturn($psr7Response);
        });

        $testingClient = $this->createTestingClient(psr18Client: $psr18Client);

        $response = $testingClient->client->send($this->requestFactory()->createRequest('GET', 'https://example.com'));

        $this->assertSame($psr7Response, $response->toPsr7Response());
    }

    /** @test */
    public function it_will_use_the_provided_request_factory_to_make_json_requests()
    {
        $request = $this->requestFactory()->createRequest('GET', 'https://example.com');

        $requestFactory = $this->mock(RequestFactoryInterface::class, function ($mock) use ($request) {
            $mock
                ->shouldReceive('createRequest')
                ->once()
                ->with(
                    'GET',
                    Mockery::on(function (UriInterface $uri) {
                        return (string) $uri === 'https://example.com';
                    }),
                )
                ->andReturn($request);
        });

        $testingClient = $this->createTestingClient(psr17RequestFactory: $requestFactory);
        $psr7Response = $this->expectSendRequest($testingClient);

        $response = $testingClient->client->json('GET', 'https://example.com');

        $this->assertSame($psr7Response, $response->toPsr7Response());
    }

    /** @test */
    public function it_will_use_the_provided_request_factory_to_make_form_requests()
    {
        $request = $this->requestFactory()->createRequest('GET', 'https://example.com');

        $requestFactory = $this->mock(RequestFactoryInterface::class, function ($mock) use ($request) {
            $mock
                ->shouldReceive('createRequest')
                ->once()
                ->with(
                    'GET',
                    Mockery::on(function (UriInterface $uri) {
                        return (string) $uri === 'https://example.com';
                    }),
                )
                ->andReturn($request);
        });

        $testingClient = $this->createTestingClient(psr17RequestFactory: $requestFactory);
        $psr7Response = $this->expectSendRequest($testingClient);

        $response = $testingClient->client->form('GET', 'https://example.com');

        $this->assertSame($psr7Response, $response->toPsr7Response());
    }

    /** @test */
    public function it_will_use_the_given_response_factory_when_json_responses_are_stubbed()
    {
        $psr7Response = $this->responseFactory()->createResponse();

        $responseFactory = $this->mock(ResponseFactoryInterface::class, function ($mock) use ($psr7Response) {
            $mock
                ->shouldReceive('createResponse')
                ->once()
                ->with(200)
                ->andReturn($psr7Response);
        });

        $testingClient = $this->createTestingClient(psr17ResponseFactory: $responseFactory);

        $client = $testingClient->client->fake()->stubResponse('https://example.com');
        $response = $client->json('GET', 'https://example.com');

        $this->assertSame($psr7Response, $response->toPsr7Response());
    }

    /** @test */
    public function it_will_use_the_given_response_factory_when_form_responses_are_stubbed()
    {
        $psr7Response = $this->responseFactory()->createResponse();

        $responseFactory = $this->mock(ResponseFactoryInterface::class, function ($mock) use ($psr7Response) {
            $mock
                ->shouldReceive('createResponse')
                ->once()
                ->with(200)
                ->andReturn($psr7Response);
        });

        $testingClient = $this->createTestingClient(psr17ResponseFactory: $responseFactory);

        $fakeClient = $testingClient->client->fake()->stubResponse('https://example.com');
        $response = $fakeClient->form('GET', 'https://example.com');

        $this->assertSame($psr7Response, $response->toPsr7Response());
    }

    /** @test */
    public function it_will_use_the_given_response_factory_when_custom_request_responses_are_stubbed()
    {
        $psr7Response = $this->responseFactory()->createResponse();

        $responseFactory = $this->mock(ResponseFactoryInterface::class, function ($mock) use ($psr7Response) {
            $mock
                ->shouldReceive('createResponse')
                ->once()
                ->with(200)
                ->andReturn($psr7Response);
        });

        $testingClient = $this->createTestingClient(psr17ResponseFactory: $responseFactory);

        $fakeClient = $testingClient->client->fake()->stubResponse('https://example.com');
        $response = $fakeClient->send($this->requestFactory()->createRequest('GET', 'https://example.com'));

        $this->assertSame($psr7Response, $response->toPsr7Response());
    }

    /** @test */
    public function it_will_use_the_given_stream_factory_when_json_responses_are_stubbed()
    {
        $body = ['id' => 1];
        $streamFactory = $this->mock(StreamFactoryInterface::class, function ($mock) use ($body) {
            $stream = $this->streamFactory()->createStream(json_encode($body));

            $mock
                ->shouldReceive('createStream')
                ->once()
                ->with(json_encode($body))
                ->andReturn($stream);
        });

        $testingClient = $this->createTestingClient(psr7StreamFactory: $streamFactory);

        $fakeClient = $testingClient->client->fake()->stubResponse('https://example.com', $body);

        $response = $fakeClient->json('GET', 'https://example.com');

        $this->assertSame($body, $response->json());
    }

    /** @test */
    public function it_will_use_the_given_stream_factory_when_form_responses_are_stubbed()
    {
        $body = http_build_query(['id' => 1]);
        $streamFactory = $this->mock(StreamFactoryInterface::class, function ($mock) use ($body) {
            $stream = $this->streamFactory()->createStream($body);

            $mock
                ->shouldReceive('createStream')
                ->once()
                ->with($body)
                ->andReturn($stream);
        });
        $testingClient = $this->createTestingClient(psr7StreamFactory: $streamFactory);
        $fakeClient = $testingClient->client->fake()->stubResponse('https://example.com', $body);

        $response = $fakeClient->form('GET', 'https://example.com');
        $this->assertSame($body, $response->getContents());
    }

    /** @test */
    public function it_will_use_the_given_stream_factory_when_json_responses_are_sent()
    {
        $body = ['id' => 1];
        $streamFactory = $this->mock(StreamFactoryInterface::class, function ($mock) use ($body) {
            $stream = $this->streamFactory()->createStream(json_encode($body));

            $mock
                ->shouldReceive('createStream')
                ->once()
                ->with(json_encode($body))
                ->andReturn($stream);
        });

        $testingClient = $this->createTestingClient(psr7StreamFactory: $streamFactory);
        $this->expectSendRequest($testingClient, function (RequestInterface $request) use ($body) {
            $this->assertSame(json_encode($body), $request->getBody()->getContents());

            return true;
        });

        $testingClient->client->json('POST', 'https://example.com', $body);
    }

    /** @test */
    public function it_will_use_the_given_stream_factory_when_form_responses_are_sent()
    {
        $body = ['id' => 1];
        $streamFactory = $this->mock(StreamFactoryInterface::class, function ($mock) use ($body) {
            $stream = $this->streamFactory()->createStream(http_build_query($body));

            $mock
                ->shouldReceive('createStream')
                ->once()
                ->with(http_build_query($body))
                ->andReturn($stream);
        });

        $testingClient = $this->createTestingClient(psr7StreamFactory: $streamFactory);
        $this->expectSendRequest($testingClient, function (RequestInterface $request) use ($body) {
            $this->assertSame(http_build_query($body), $request->getBody()->getContents());

            return true;
        });

        $testingClient->client->form('POST', 'https://example.com', $body);
    }

    /** @test */
    public function it_will_use_the_given_uri_factory_when_a_json_request_is_sent()
    {
        $url = 'https://example.com';
        $uriFactory = $this->mock(UriFactoryInterface::class, function ($mock) use ($url) {
            $uri = $this->uriFactory()->createUri($url);

            $mock
                ->shouldReceive('createUri')
                ->once()
                ->andReturn($uri);
        });

        $testingClient = $this->createTestingClient(psr7UriFactory: $uriFactory);
        $this->expectSendRequest($testingClient, function (RequestInterface $request) use ($url) {
            $this->assertSame($url, (string) $request->getUri());

            return true;
        });

        $testingClient->client->json('GET', $url);
    }

    /** @test */
    public function it_will_use_the_given_uri_factory_when_a_form_request_is_sent()
    {
        $url = 'https://example.com';
        $uriFactory = $this->mock(UriFactoryInterface::class, function ($mock) use ($url) {
            $uri = $this->uriFactory()->createUri($url);

            $mock
                ->shouldReceive('createUri')
                ->once()
                ->andReturn($uri);
        });

        $testingClient = $this->createTestingClient(psr7UriFactory: $uriFactory);
        $this->expectSendRequest($testingClient, function (RequestInterface $request) use ($url) {
            $this->assertSame($url, (string) $request->getUri());

            return true;
        });

        $testingClient->client->form('GET', $url);
    }
}
