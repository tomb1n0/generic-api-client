<?php

namespace Tomb1n0\GenericApiClient\Http;

use Psr\Http\Client\ClientInterface;
use Tomb1n0\GenericApiClient\Options;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Client as GuzzleHttpClient;
use GuzzleHttp\Psr7\Request as GuzzleRequest;

class Client
{
    protected ClientInterface $client;
    protected Options $options;
    protected MiddlewareDispatcher $middlewareDispatcher;

    /**
     * Create a new Client
     *
     * @param ClientInterface $client The PSR-18 Client to utilise when making requests, if not given a Guzzle one will be used
     * @param Options|null $options The Options to configure this client with. For available options see the Options class.
     */
    public function __construct(?ClientInterface $client = null, ?Options $options = null)
    {
        $this->client = $client ?? new GuzzleHttpClient();
        $this->options = $options ?? new Options();
        $this->middlewareDispatcher = new MiddlewareDispatcher($this->options->middleware);
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
        /**
         * If this isn't a GET request, put the parameters into the body.
         *
         * If it is a GET request, the build URL method will handle pushing these parameters into the query string
         */
        $body = strtolower($method) !== 'get' ? json_encode($params) : null;

        $request = new GuzzleRequest(
            $method,
            $this->buildUrl($method, $url, $params),
            [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
            $body,
        );

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
    public function request(string $method, string $url, array $params = []): Response
    {
        /**
         * If this isn't a GET request, put the parameters into the body.
         *
         * If it is a GET request, the build URL method will handle pushing these parameters into the query string
         */
        $body = strtolower($method) !== 'get' ? http_build_query($params) : null;

        $request = new GuzzleRequest(
            $method,
            $this->buildUrl($method, $url, $params),
            [
                'Content-Type' => 'application/x-www-form-urlencoded',
            ],
            $body,
        );

        return $this->send($request);
    }

    public function send(RequestInterface $request): Response
    {
        $response = $this->middlewareDispatcher->dispatch(function (RequestInterface $request): ResponseInterface {
            return $this->client->sendRequest($request);
        }, $request);

        return new Response($this, $request, $response, $this->options);
    }

    protected function buildUrl(string $method, string $url, array $options)
    {
        $url = $url;

        if (isset($this->options->baseUrl)) {
            $url = $this->options->baseUrl . $url;
        }

        if (strtolower($method) === 'get') {
            $url = $url . '?' . http_build_query($options);
        }

        return $url;
    }
}
