<?php

namespace Tomb1n0\GenericApiClient\Http;

use Psr\Http\Message\UriInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use PHPUnit\Framework\Assert as PHPUnit;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Tomb1n0\GenericApiClient\Contracts\ClientContract;
use Tomb1n0\GenericApiClient\Http\Traits\ClientFactoryMethods;
use Tomb1n0\GenericApiClient\Exceptions\ClientNotFakedException;
use Tomb1n0\GenericApiClient\Contracts\PaginationHandlerContract;
use Tomb1n0\GenericApiClient\Contracts\FakeResponseMatcherContract;

class Client implements ClientContract
{
    use ClientFactoryMethods;

    /**
     * The Underlying PSR-18 Client we will be using to make requests
     *
     * @var ClientInterface
     */
    protected ClientInterface $client;

    /**
     * The middleware dispatcher we will be using to send the request through before sending it to our PSR-18 client.
     *
     * @var MiddlewareDispatcher
     */
    protected MiddlewareDispatcher $middlewareDispatcher;

    /**
     * The PSR-17 Request Factory to use when creating PSR-7 requests
     *
     * @var RequestFactoryInterface
     */
    protected RequestFactoryInterface $requestFactory;

    /**
     * The PSR-17 Response Factory to use when creating PSR-7 responses
     *
     * @var ResponseFactoryInterface
     */
    protected ResponseFactoryInterface $responseFactory;

    /**
     * The stream interface to use when creating PSR-7 Request & Response bodies
     *
     * @var StreamFactoryInterface
     */
    protected StreamFactoryInterface $streamFactory;

    /**
     * The URI factory to use when creating URIs
     *
     * @var UriFactoryInterface
     */
    protected UriFactoryInterface $uriFactory;

    /**
     * The BaseURL for the API.
     *
     * @var string|null
     */
    protected ?string $baseUrl = null;

    /**
     * The Pagination Handler for this API.
     *
     * Used when fetching next pages from responses.
     *
     * @var PaginationHandlerContract|null
     */
    protected ?PaginationHandlerContract $paginationHandler = null;

    /**
     * The requests we have recorded so far.
     *
     * Only used when we have faked the PSR-18 client. e.g. Client::fake()->json('GET', 'https://dummyjson.com/products')
     *
     * @var array<int, RecordedRequest>
     */
    protected array $recordedRequests = [];

    /**
     * Construct a new Client.
     */
    public function __construct(
        ClientInterface $client,
        RequestFactoryInterface $requestFactory,
        ResponseFactoryInterface $responseFactory,
        StreamFactoryInterface $streamFactory,
        UriFactoryInterface $uriFactory,
    ) {
        $this->client = $client;
        $this->requestFactory = $requestFactory;
        $this->responseFactory = $responseFactory;
        $this->streamFactory = $streamFactory;
        $this->uriFactory = $uriFactory;

        $this->middlewareDispatcher = new MiddlewareDispatcher();
    }

    /**
     * Return a new instance of this Client with a Faked PSR-18 client under the hood.
     *
     * Note that this returns a brand new instance with the client swapped out.
     *
     * @return static
     */
    public function fake(): static
    {
        $copy = clone $this;
        $copy->client = new FakePsr18Client($this->responseFactory);

        return $copy;
    }

    public function preventStrayRequests(): static
    {
        if (!$this->client instanceof FakePsr18Client) {
            throw new ClientNotFakedException('Please call ->fake() first.');
        }

        $this->client->preventStrayRequests();

        return $this;
    }

    /**
     * Stub the given URL with the given fake response.
     *
     * Please call ->fake() first and call this on the returned client.
     *
     * @param array<string, string> $headers
     */
    public function stubResponse(string $url, mixed $body = null, int $status = 200, array $headers = []): static
    {
        if (!$this->client instanceof FakePsr18Client) {
            throw new ClientNotFakedException('Please call ->fake() first.');
        }

        $this->client->stubResponse(
            $url,
            new FakeResponse($this->responseFactory, $this->streamFactory, $body, $status, $headers),
        );

        return $this;
    }

    /**
     * Stub the given URL with the given fake response.
     *
     * Please call ->fake() first and call this on the returned client.
     *
     * @param array<string, string> $headers
     */
    public function stubResponseWithCustomMatcher(
        FakeResponseMatcherContract $matcher,
        mixed $body = null,
        int $status = 200,
        array $headers = [],
    ): static {
        if (!$this->client instanceof FakePsr18Client) {
            throw new ClientNotFakedException('Please call ->fake() first.');
        }

        $this->client->stubResponseWithCustomMatcher(
            $matcher,
            new FakeResponse($this->responseFactory, $this->streamFactory, $body, $status, $headers),
        );

        return $this;
    }

    /**
     * Defer to the given callback to assert if a given request was sent.
     *
     * @param callable $assertionCallback
     * @return void
     */
    public function assertSent(callable $assertionCallback): void
    {
        PHPUnit::assertTrue(count($this->recorded($assertionCallback)) > 0, 'An expected request was not recorded.');
    }

    /**
     * Defer to the given callback to assert if a given request was not sent.
     *
     * @param callable $assertionCallback
     * @return void
     */
    public function assertNotSent(callable $assertionCallback): void
    {
        PHPUnit::assertFalse(count($this->recorded($assertionCallback)) > 0, 'An unexpected request was recorded.');
    }

    /**
     * Return the recorded requests, optionally filtered by a callback
     *
     * @return array<int, RecordedRequest>
     */
    public function recorded(callable $filterCallback = null): array
    {
        if ($filterCallback) {
            $filteredRequests = array_filter($this->recordedRequests, function (RecordedRequest $recordedRequest) use (
                $filterCallback,
            ) {
                return $filterCallback($recordedRequest->request);
            });

            return array_values($filteredRequests);
        }

        return $this->recordedRequests;
    }

    /**
     * Perform a JSON request
     *
     * @param string $method
     * @param string $url
     * @param array<int|string, mixed> $params
     * @return Response
     *
     * @throws \Psr\Http\Client\ClientExceptionInterface If an error happens while processing the request.
     */
    public function json(string $method, string $url, array $params = []): Response
    {
        $method = strtoupper($method);

        $request = $this->requestFactory
            ->createRequest($method, $this->buildUrl($method, $url, $params))
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Accept', 'application/json');

        if ($method !== 'GET') {
            $request = $request->withBody($this->streamFactory->createStream(json_encode($params)));
        }

        return $this->send($request);
    }

    /**
     * Perform a x-www-form-urlencoded request
     *
     * @param string $method
     * @param string $url
     * @param array<int|string, mixed> $params
     * @return Response
     *
     * @throws \Psr\Http\Client\ClientExceptionInterface If an error happens while processing the request.
     */
    public function form(string $method, string $url, array $params = []): Response
    {
        $method = strtoupper($method);

        $request = $this->requestFactory
            ->createRequest($method, $this->buildUrl($method, $url, $params))
            ->withHeader('Content-Type', 'application/x-www-form-urlencoded');

        if ($method !== 'GET') {
            $request = $request->withBody($this->streamFactory->createStream(http_build_query($params)));
        }

        return $this->send($request);
    }

    /**
     * Actually send the request.
     *
     * This is useful because you might want to send a handcrafted PSR-7 request instead of relying on json/form methods
     *
     * @param RequestInterface $request
     * @return Response
     *
     * @throws \Psr\Http\Client\ClientExceptionInterface If an error happens while processing the request.
     */
    public function send(RequestInterface $request): Response
    {
        $requestResponsePair = $this->sendRequestThroughMiddlewareStack($request);

        $response = new Response(
            $this,
            $requestResponsePair->request,
            $requestResponsePair->response,
            $this->paginationHandler,
        );

        if ($this->client instanceof FakePsr18Client) {
            $this->recordedRequests[] = new RecordedRequest($requestResponsePair->request, $response);
        }

        return $response;
    }

    /**
     * Send the given request through the middleware stack
     *
     * @param RequestInterface $request
     * @return RequestResponsePair
     *
     * @throws \Psr\Http\Client\ClientExceptionInterface If an error happens while processing the request.
     */
    protected function sendRequestThroughMiddlewareStack(RequestInterface $request): RequestResponsePair
    {
        return $this->middlewareDispatcher->dispatch(function (RequestInterface $request): ResponseInterface {
            return $this->client->sendRequest($request);
        }, $request);
    }

    /**
     * Build the URL used for the request.
     *
     * If this is a GET request, we will automatically add the options into the query string.
     *
     * @param string $method
     * @param string $url
     * @param array<int|string, mixed> $options
     * @return UriInterface
     */
    protected function buildUrl(string $method, string $url, array $options): UriInterface
    {
        $method = strtoupper($method);

        /**
         * Check if we were given an already valid URL.
         *
         * Sometimes it is useful to set a base URL for the 90% use case, and then re-use the same client for
         * a completely different URL.
         *
         * Maybe there's a separate URL for fetching an access token etc.
         */
        $isValidUrl = filter_var($url, FILTER_VALIDATE_URL);

        // If we have a base url, and we weren't given a valid URL, prefix our base url
        if (isset($this->baseUrl) && !$isValidUrl) {
            $url = $this->baseUrl . $url;
        }

        // If we're a GET request, tack on the options as query parameters
        if ($method === 'GET' && !empty($options)) {
            $url = $url . '?' . http_build_query($options);
        }

        return $this->uriFactory->createUri($url);
    }
}
