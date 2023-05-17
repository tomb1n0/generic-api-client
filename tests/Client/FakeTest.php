<?php

namespace Tomb1n0\GenericApiClient\Tests\Client;

use GuzzleHttp\Psr7\HttpFactory;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Tomb1n0\GenericApiClient\Tests\BaseTestCase;
use Tomb1n0\GenericApiClient\Http\RecordedRequest;
use Tomb1n0\GenericApiClient\Exceptions\ClientNotFakedException;
use Tomb1n0\GenericApiClient\Exceptions\NoMatchingStubbedResponseException;

class FakeTest extends BaseTestCase
{
    /** @test */
    public function faking_the_client_prevents_requests_from_being_sent_through_the_provided_psr18_client()
    {
        $psr18Client = $this->mock(ClientInterface::class, function ($mock) {
            $mock->shouldNotReceive('sendRequest');
        });

        $testingClient = $this->createTestingClient(psr18Client: $psr18Client);
        $client = $testingClient->client->fake();

        $this->expectException(NoMatchingStubbedResponseException::class);
        $client->json('GET', 'https://example.com');
    }

    /** @test */
    public function faking_the_client_returns_a_different_instance()
    {
        $client = $this->createTestingClient()->client;

        $this->assertNotSame($client, $client->fake());
    }

    /** @test */
    public function trying_to_stub_a_response_without_calling_fake_throws_an_exception()
    {
        $client = $this->createTestingClient()->client;

        $this->expectException(ClientNotFakedException::class);

        $client->stubResponse('https://example.com');
    }

    /** @test */
    public function faking_the_client_and_making_a_non_matching_request_will_throw_an_exception()
    {
        $testingClient = $this->createTestingClient();
        $client = $testingClient->client->fake();

        try {
            $client->json('GET', 'https://example.com');
        } catch (NoMatchingStubbedResponseException $e) {
            $this->assertSame('No stubbed response for https://example.com', $e->getMessage());
        }
    }

    /** @test */
    public function can_stub_json_responses()
    {
        $testingClient = $this->createTestingClient();
        $client = $testingClient->client
            ->fake()
            ->stubResponse('https://example.com', ['id' => 1, 'name' => 'response'], 200, [
                'X-Custom-Response-Header' => 'custom-header',
            ])
            ->stubResponse('https://foo.com', null, 404, [
                'X-Custom-Different-Header' => 'different-header',
            ]);

        $exampleResponse = $client->json('GET', 'https://example.com');
        $fooResponse = $client->json('GET', 'https://foo.com');

        $examplePsr7Response = $exampleResponse->toPsr7Response();
        $fooPsr7Response = $fooResponse->toPsr7Response();

        $this->assertSame(200, $examplePsr7Response->getStatusCode());
        $this->assertSame(
            json_encode(['id' => 1, 'name' => 'response']),
            $examplePsr7Response->getBody()->getContents(),
        );
        $this->assertSame('custom-header', $examplePsr7Response->getHeaderLine('X-Custom-Response-Header'));

        $this->assertSame(404, $fooPsr7Response->getStatusCode());
        $this->assertSame('', $fooPsr7Response->getBody()->getContents());
        $this->assertSame('different-header', $fooPsr7Response->getHeaderLine('X-Custom-Different-Header'));
    }

    /**
     * @test
     * @dataProvider bodyTypesProvider
     */
    public function can_stub_a_request_with_body_for_a_json_request(mixed $body, string $expected)
    {
        $testingClient = $this->createTestingClient();
        $client = $testingClient->client->fake()->stubResponse('https://example.com', $body);

        $exampleResponse = $client->json('GET', 'https://example.com');
        $examplePsr7Response = $exampleResponse->toPsr7Response();

        $this->assertSame($expected, $examplePsr7Response->getBody()->getContents());
    }

    /**
     * @test
     * @dataProvider bodyTypesProvider
     */
    public function can_stub_a_request_with_body_for_a_form_request(mixed $body, string $expected)
    {
        $testingClient = $this->createTestingClient();
        $client = $testingClient->client->fake()->stubResponse('https://example.com', $body);

        $exampleResponse = $client->form('GET', 'https://example.com');
        $examplePsr7Response = $exampleResponse->toPsr7Response();

        $this->assertSame($expected, $examplePsr7Response->getBody()->getContents());
    }

    /** @test */
    public function can_use_the_built_in_assertion_functions_with_json()
    {
        $testingClient = $this->createTestingClient();
        $client = $testingClient->client
            ->fake()
            ->stubResponse('https://example.com')
            ->stubResponse('https://foo.com');

        $client->json('GET', 'https://example.com');

        $client->assertSent(function (RequestInterface $requestInterface) {
            return $requestInterface->getMethod() === 'GET' &&
                (string) $requestInterface->getUri() === 'https://example.com';
        });

        $client->assertNotSent(function (RequestInterface $requestInterface) {
            return $requestInterface->getMethod() === 'GET' &&
                (string) $requestInterface->getUri() === 'https://foo.com';
        });
    }

    /** @test */
    public function can_stub_form_requests()
    {
        $testingClient = $this->createTestingClient();
        $client = $testingClient->client
            ->fake()
            ->stubResponse('https://example.com', ['id' => 1, 'name' => 'response'], 200, [
                'X-Custom-Response-Header' => 'custom-header',
            ])
            ->stubResponse('https://foo.com', null, 404, [
                'X-Custom-Different-Header' => 'different-header',
            ]);

        $exampleResponse = $client->form('GET', 'https://example.com');
        $fooResponse = $client->form('GET', 'https://foo.com');

        $examplePsr7Response = $exampleResponse->toPsr7Response();
        $fooPsr7Response = $fooResponse->toPsr7Response();

        $this->assertSame(200, $examplePsr7Response->getStatusCode());
        $this->assertSame(
            json_encode(['id' => 1, 'name' => 'response']),
            $examplePsr7Response->getBody()->getContents(),
        );
        $this->assertSame('custom-header', $examplePsr7Response->getHeaderLine('X-Custom-Response-Header'));

        $this->assertSame(404, $fooPsr7Response->getStatusCode());
        $this->assertSame('', $fooPsr7Response->getBody()->getContents());
        $this->assertSame('different-header', $fooPsr7Response->getHeaderLine('X-Custom-Different-Header'));
    }

    /** @test */
    public function can_use_built_in_assertion_functions_with_form()
    {
        $testingClient = $this->createTestingClient();
        $client = $testingClient->client
            ->fake()
            ->stubResponse('https://example.com')
            ->stubResponse('https://foo.com');

        $client->form('GET', 'https://example.com');

        $client->assertSent(function (RequestInterface $requestInterface) {
            return $requestInterface->getMethod() === 'GET' &&
                (string) $requestInterface->getUri() === 'https://example.com';
        });

        $client->assertNotSent(function (RequestInterface $requestInterface) {
            return $requestInterface->getMethod() === 'GET' &&
                (string) $requestInterface->getUri() === 'https://foo.com';
        });
    }

    /** @test */
    public function can_stub_custom_requests()
    {
        $testingClient = $this->createTestingClient();
        $client = $testingClient->client
            ->fake()
            ->stubResponse('https://example.com', ['id' => 1, 'name' => 'response'], 200, [
                'X-Custom-Response-Header' => 'custom-header',
            ])
            ->stubResponse('https://foo.com', null, 404, [
                'X-Custom-Different-Header' => 'different-header',
            ]);

        $exampleResponse = $client->send($this->requestFactory()->createRequest('GET', 'https://example.com'));
        $fooResponse = $client->send($this->requestFactory()->createRequest('GET', 'https://foo.com'));

        $examplePsr7Response = $exampleResponse->toPsr7Response();
        $fooPsr7Response = $fooResponse->toPsr7Response();

        $this->assertSame(200, $examplePsr7Response->getStatusCode());
        $this->assertSame(
            json_encode(['id' => 1, 'name' => 'response']),
            $examplePsr7Response->getBody()->getContents(),
        );
        $this->assertSame('custom-header', $examplePsr7Response->getHeaderLine('X-Custom-Response-Header'));

        $this->assertSame(404, $fooPsr7Response->getStatusCode());
        $this->assertSame('', $fooPsr7Response->getBody()->getContents());
        $this->assertSame('different-header', $fooPsr7Response->getHeaderLine('X-Custom-Different-Header'));
    }

    /** @test */
    public function can_use_built_in_assertion_functions_with_custom_request()
    {
        $testingClient = $this->createTestingClient();
        $client = $testingClient->client
            ->fake()
            ->stubResponse('https://example.com')
            ->stubResponse('https://foo.com');

        $client->send($this->requestFactory()->createRequest('GET', 'https://example.com'));

        $client->assertSent(function (RequestInterface $requestInterface) {
            return $requestInterface->getMethod() === 'GET' &&
                (string) $requestInterface->getUri() === 'https://example.com';
        });

        $client->assertNotSent(function (RequestInterface $requestInterface) {
            return $requestInterface->getMethod() === 'GET' &&
                (string) $requestInterface->getUri() === 'https://foo.com';
        });
    }

    /** @test */
    public function can_fetch_recorded_requests()
    {
        $testingClient = $this->createTestingClient();
        $client = $testingClient->client
            ->fake()
            ->stubResponse('https://example.com', ['id' => 1, 'name' => 'response'], 200, [
                'X-Custom-Response-Header' => 'custom-header',
            ])
            ->stubResponse('https://foo.com', null, 404, [
                'X-Custom-Different-Header' => 'different-header',
            ]);

        $client->json('GET', 'https://example.com');
        $client->json('GET', 'https://foo.com');

        $recorded = $client->recorded();

        $this->assertCount(2, $recorded);

        $this->assertSame('https://example.com', (string) $recorded[0]->request->getUri());
        $this->assertSame('https://foo.com', (string) $recorded[1]->request->getUri());
    }

    /** @test */
    public function can_filter_recorded_requests()
    {
        $testingClient = $this->createTestingClient();
        $client = $testingClient->client
            ->fake()
            ->stubResponse('https://example.com', ['id' => 1, 'name' => 'response'], 200, [
                'X-Custom-Response-Header' => 'custom-header',
            ])
            ->stubResponse('https://foo.com', null, 404, [
                'X-Custom-Different-Header' => 'different-header',
            ]);

        $client->json('GET', 'https://example.com');
        $client->json('GET', 'https://foo.com');

        $recorded = $client->recorded(function (RequestInterface $request) {
            return (string) $request->getUri() === 'https://foo.com';
        });

        $this->assertCount(1, $recorded);

        $this->assertSame('https://foo.com', (string) $recorded[0]->request->getUri());
    }

    /** @test */
    public function it_wont_record_requests_if_the_client_has_not_been_faked()
    {
        $mockResponse = $this->responseFactory()->createResponse(200, 'OK');

        $mockClient = $this->mock(ClientInterface::class);
        $mockClient
            ->shouldReceive('sendRequest')
            ->once()
            ->andReturn($mockResponse);

        $testingClient = $this->createTestingClient(psr18Client: $mockClient);
        $client = $testingClient->client;

        $client->json('GET', 'https://example.com');

        $this->assertCount(0, $client->recorded());
    }
}
