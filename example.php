<?php

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Tomb1n0\GenericApiClient\Http\Client;
use GuzzleHttp\Client as GuzzleHttpClient;
use Tomb1n0\GenericApiClient\Http\Response;
use Tomb1n0\GenericApiClient\Http\FakeResponse;
use Tomb1n0\GenericApiClient\Contracts\MiddlewareContract;
use Tomb1n0\GenericApiClient\Contracts\PaginationHandlerContract;

require_once 'vendor/autoload.php';

class PaginationHandler implements PaginationHandlerContract
{
    public function hasNextPage(Response $response): bool
    {
        $total = $response->json('total');
        $skip = $response->json('skip');
        $limit = $response->json('limit');

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

// Create an API that uses the dummyjson.com API
$dummyJsonClient = (new Client())
    ->withPsr18Client(new GuzzleHttpClient())
    ->withBaseUrl('https://dummyjson.com')
    ->withPaginationHandler(new PaginationHandler())
    ->withMiddleware([new AuthenticationMiddleware(), new LoggerMiddleware(), new ProfilingMiddleware()]);

// Fetch Products, and paginate them
$dummyJsonClient
    ->json('GET', '/products', [
        'limit' => 25,
    ])
    ->forEachPage(function (Response $response) {
        $products = $response->json('products', []);

        // Do stuff with this page of products
        // var_dump($products);
    });

// Create another API that uses the dummyjson.com API but stub the responses out.
// The way this works is that it replaces the given PSR-18 client with a fake one that returns stubbed responses.
$dummyFakeJsonClient = (new Client())
    ->withBaseUrl('https://dummyjson.com')
    ->withMiddleware([new AuthenticationMiddleware(), new LoggerMiddleware(), new ProfilingMiddleware()])
    ->fake([
        'https://dummyjson.com/products' => new FakeResponse(
            [
                'products' => [['id' => 1, 'name' => 'iPhone X'], ['id' => 2, 'name' => 'iPhone 11']],
            ],
            200,
        ),
    ]);

$response = $dummyFakeJsonClient->json('GET', '/products');
// $response->json() will have ['products' => [['id' => 1, 'name' => 'iPhone X'], ['id' => 2, 'name' => 'iPhone 11']]] in the body!
