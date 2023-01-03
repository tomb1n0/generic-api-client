<?php

use Tomb1n0\GenericApiClient\Options;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Tomb1n0\GenericApiClient\Http\Client;
use GuzzleHttp\Client as GuzzleHttpClient;
use Tomb1n0\GenericApiClient\Http\Response;
use Tomb1n0\GenericApiClient\Contracts\MiddlewareContract;
use Tomb1n0\GenericApiClient\Contracts\PaginationHandlerContract;

require_once 'vendor/autoload.php';

class PaginationHandler implements PaginationHandlerContract
{
    public function hasNextPage(Response $response): bool
    {
        $contents = $response->getJsonContents();

        $total = isset($contents['total']) ? $contents['total'] : null;
        $skip = isset($contents['skip']) ? $contents['skip'] : null;
        $limit = isset($contents['limit']) ? $contents['limit'] : null;

        if (is_null($total) || is_null($skip) || is_null($limit)) {
            return false;
        }

        return $total > $skip + $limit;
    }

    public function getNextPage(Response $response): RequestInterface
    {
        $originalRequest = $response->getRequest();
        $uri = $originalRequest->getUri();

        $originalQuery = $uri->getQuery();
        $originalQueryArray = [];

        // Parse the query string into an array
        parse_str($originalQuery, $originalQueryArray);

        // Figure out what the skip should be
        $skip = isset($originalQueryArray['skip']) ? $originalQueryArray['skip'] : 0;
        $newSkip = $skip + $originalQueryArray['limit'];

        // Build the new URI
        $newUri = $uri->withQuery(
            http_build_query(
                array_merge($originalQueryArray, [
                    'skip' => $newSkip,
                ]),
            ),
        );

        // Return the original request but with the new URI, ergo new parameters
        return $originalRequest->withUri($newUri);
    }
}

class ProfilingMiddleware implements MiddlewareContract
{
    public function handle(RequestInterface $request, callable $next): ResponseInterface
    {
        $start = microtime(true);

        $response = $next($request);

        var_dump('Done! Request took ' . microtime(true) - $start . ' seconds');

        return $response;
    }
}

class LoggerMiddleware implements MiddlewareContract
{
    public function handle(RequestInterface $request, callable $next): ResponseInterface
    {
        var_dump("Performing {$request->getMethod()} request to {$request->getUri()}");

        return $next($request);
    }
}

class AuthenticationMiddleware implements MiddlewareContract
{
    public function handle(RequestInterface $request, callable $next): ResponseInterface
    {
        $request = $request->withHeader('Authorization', 'Bearer 123456789');

        return $next($request);
    }
}

// Create the Client itself
$client = new Client(
    client: new GuzzleHttpClient(), // Pass in a custom PSR-18 compliant client
    options: new Options( // Pass in various options to configure the client
        baseUrl: 'https://dummyjson.com', // A baseurl to use
        paginationHandler: new PaginationHandler(), // A pagination handler, this is used to determine if a response has a next page etc
        middleware: [new AuthenticationMiddleware(), new LoggerMiddleware(), new ProfilingMiddleware()], // Middleware to dispatch the request through
    ),
);

// Fetch Products
$client
    ->json('GET', '/products', [
        'limit' => 25,
    ])
    ->forEachPage(function (Response $response) {
        $products = $response->getJsonContents()['products'];

        // Do stuff with this page of products
    });
