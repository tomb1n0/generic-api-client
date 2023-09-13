<?php

namespace Tomb1n0\GenericApiClient\Tests\Client;

use Psr\Http\Message\RequestInterface;
use Tomb1n0\GenericApiClient\Http\Client;
use Psr\Http\Message\RequestFactoryInterface;
use Tomb1n0\GenericApiClient\Tests\BaseTestCase;

class JsonTest extends BaseTestCase
{
    /**
     * @test
     * @dataProvider httpVerbsProvider
     */
    public function can_make_a_json_request(string $verb)
    {
        $url = 'https://example.com';

        $testingClient = $this->createTestingClient();
        $psr7Request = null;

        $psr7Response = $this->expectSendRequest($testingClient, function (RequestInterface $request) use (
            $url,
            $verb,
            &$psr7Request,
        ) {
            $this->assertSame('application/json', $request->getHeaderLine('Content-Type'));
            $this->assertSame('application/json', $request->getHeaderLine('Accept'));

            $this->assertSame($verb, strtoupper($request->getMethod()));
            $this->assertSame($url, (string) $request->getUri());

            $psr7Request = $request;

            return true;
        });

        $response = $testingClient->client->json($verb, $url);

        $this->assertSame($psr7Request, $response->toPsr7Request());
        $this->assertSame($psr7Response, $response->toPsr7Response());
    }

    /** @test */
    public function making_a_get_request_with_options_puts_them_in_the_query_string()
    {
        $url = 'https://example.com';

        $testingClient = $this->createTestingClient();

        $this->expectSendRequest($testingClient, function (RequestInterface $request) use ($url) {
            $this->assertSame('application/json', $request->getHeaderLine('Content-Type'));
            $this->assertSame('application/json', $request->getHeaderLine('Accept'));

            $this->assertSame('GET', strtoupper($request->getMethod()));
            $this->assertSame($url . '?active=1&page=12', (string) $request->getUri());

            return true;
        });

        $testingClient->client->json('GET', $url, ['active' => 1, 'page' => 12]);
    }

    /**
     * @test
     * @dataProvider httpVerbsProvider
     */
    public function can_make_a_request_when_a_base_url_is_set(string $verb)
    {
        $baseUrl = 'https://example.com';

        $testingClient = $this->createTestingClient(function (Client $client) use ($baseUrl) {
            return $client->withBaseUrl($baseUrl);
        });

        $this->expectSendRequest($testingClient, function (RequestInterface $request) use ($baseUrl, $verb) {
            $this->assertSame('application/json', $request->getHeaderLine('Content-Type'));
            $this->assertSame('application/json', $request->getHeaderLine('Accept'));

            $this->assertSame($verb, strtoupper($request->getMethod()));
            $this->assertSame($baseUrl . '/products', (string) $request->getUri());

            return true;
        });

        $testingClient->client->json($verb, '/products');
    }

    /**
     * @test
     * @dataProvider httpVerbsProvider
     */
    public function making_a_request_when_a_base_url_is_set_to_a_full_url_works(string $verb)
    {
        $baseUrl = 'https://example.com';
        $url = 'https://other-example.com/products';

        $testingClient = $this->createTestingClient(function (Client $client) use ($baseUrl) {
            return $client->withBaseUrl($baseUrl);
        });

        $this->expectSendRequest($testingClient, function (RequestInterface $request) use ($url, $verb) {
            $this->assertSame('application/json', $request->getHeaderLine('Content-Type'));
            $this->assertSame('application/json', $request->getHeaderLine('Accept'));

            $this->assertSame($verb, strtoupper($request->getMethod()));
            $this->assertSame($url, (string) $request->getUri());

            return true;
        });

        $testingClient->client->json($verb, $url);
    }

    /**
     * @test
     * @dataProvider httpVerbsExcludingGetProvider
     */
    public function making_a_non_get_request_with_options_puts_them_in_the_body(string $verb)
    {
        $url = 'https://example.com';

        $testingClient = $this->createTestingClient();

        $this->expectSendRequest($testingClient, function (RequestInterface $request) use ($url, $verb) {
            $this->assertSame('application/json', $request->getHeaderLine('Content-Type'));
            $this->assertSame('application/json', $request->getHeaderLine('Accept'));

            $this->assertSame($verb, strtoupper($request->getMethod()));
            $this->assertSame($url, (string) $request->getUri());
            $this->assertSame(json_encode(['active' => 1, 'page' => 12]), $request->getBody()->getContents());

            return true;
        });

        $testingClient->client->json($verb, $url, ['active' => 1, 'page' => 12]);
    }

    /**
     * @test
     * @dataProvider httpVerbsProvider
     */
    public function can_provide_custom_headers(string $verb)
    {
        $url = 'https://example.com';

        $testingClient = $this->createTestingClient();
        $psr7Request = null;

        $psr7Response = $this->expectSendRequest($testingClient, function (RequestInterface $request) use (
            $url,
            $verb,
            &$psr7Request,
        ) {
            $this->assertSame('application/json', $request->getHeaderLine('Content-Type'));
            $this->assertSame('application/json', $request->getHeaderLine('Accept'));
            $this->assertSame('Custom Value', $request->getHeaderLine('X-Custom-Header'));

            $this->assertSame($verb, strtoupper($request->getMethod()));
            $this->assertSame($url, (string) $request->getUri());

            $psr7Request = $request;

            return true;
        });

        $response = $testingClient->client->json($verb, $url, [], ['X-Custom-Header' => 'Custom Value']);

        $this->assertSame($psr7Request, $response->toPsr7Request());
        $this->assertSame($psr7Response, $response->toPsr7Response());
    }
}
