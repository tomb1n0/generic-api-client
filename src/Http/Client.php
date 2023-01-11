<?php

namespace Tomb1n0\GenericApiClient\Http;

use RuntimeException;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\Utils;
use GuzzleHttp\Psr7\HttpFactory;
use Psr\Http\Message\UriInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Client as GuzzleHttpClient;
use Psr\Http\Message\RequestFactoryInterface;
use Tomb1n0\GenericApiClient\Contracts\ClientContract;
use Tomb1n0\GenericApiClient\Http\Traits\ClientFactoryMethods;
use Tomb1n0\GenericApiClient\Contracts\PaginationHandlerContract;

class Client implements ClientContract
{
    protected ClientInterface $client;
    protected MiddlewareDispatcher $middlewareDispatcher;
    protected RequestFactoryInterface $requestFactory;

    protected ?string $baseUrl = null;
    protected ?PaginationHandlerContract $paginationHandler = null;

    protected bool $preventStrayRequests = false;
    protected array $stubbedResponses = [];

    use ClientFactoryMethods;

    /**
     * Construct the defaults for a Client
     */
    public function __construct()
    {
        $this->client = new GuzzleHttpClient();
        $this->middlewareDispatcher = new MiddlewareDispatcher();
        $this->requestFactory = new HttpFactory();
    }

    public static function fake(array $stubbedResponses = [])
    {
        $client = new Client();
        $client->withPsr18Client(new FakePsr18Client());

        foreach ($stubbedResponses as $url => $fakeResponse) {
            $client = $client->stubResponse($url, $fakeResponse);
        }

        return $client;
    }

    public function stubResponse(string $url, FakeResponse $fakeResponse)
    {
        if (!$this->client instanceof FakePsr18Client) {
            throw new RuntimeException('Please call ::fake() first');
        }

        $this->client->stubResponse($url, $fakeResponse);

        return $this;
    }

    /**
     * Perform a JSON request
     *
     * @param string $method
     * @param string $url
     * @param array $params
     * @return Response
     */
    public function json(string $method, string $url, array $params = []): Response
    {
        $request = $this->requestFactory
            ->createRequest($method, $this->buildUrl($method, $url, $params))
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Accept', 'application/json');

        if (strtolower($method) !== 'get') {
            $request = $request->withBody(Utils::streamFor(json_encode($params)));
        }

        return $this->send($request);
    }

    /**
     * Perform a x-www-form-urlencoded request
     *
     * @param string $method
     * @param string $url
     * @param array $params
     * @return Response
     */
    public function form(string $method, string $url, array $params = []): Response
    {
        $request = $this->requestFactory
            ->createRequest($method, $this->buildUrl($method, $url, $params))
            ->withHeader('Content-Type', 'application/x-www-form-urlencoded');

        if (strtolower($method) !== 'get') {
            $request = $request->withBody(Utils::streamFor(http_build_query($params)));
        }

        return $this->send($request);
    }

    /**
     * Actually send the request, dispatching the request through our middleware stack.
     *
     * This is used by the json and form methods internally, however this is public so you can send custom PSR-7 requests as needed.
     *
     * @param RequestInterface $request
     * @return Response
     */
    public function send(RequestInterface $request): Response
    {
        return new Response(
            $this,
            $request,
            $this->sendRequestThroughMiddlewareStack($request),
            $this->paginationHandler,
        );
    }

    /**
     * Send the given request through the middleware stack
     *
     * @param RequestInterface $request
     * @return ResponseInterface
     */
    protected function sendRequestThroughMiddlewareStack(RequestInterface $request): ResponseInterface
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
     * @param array $options
     * @return string
     */
    protected function buildUrl(string $method, string $url, array $options): UriInterface
    {
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
        if (strtolower($method) === 'get') {
            $url = $url . '?' . http_build_query($options);
        }

        return new Uri($url);
    }
}
