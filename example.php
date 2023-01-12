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

$dummyAPI = Client::fake([
    'https://dummyjson.com/products' => new FakeResponse(),
])
    ->withBaseUrl('https://dummyjson.com')
    ->withMiddleware([new AfterMiddleware(), new AuthenticationMiddleware('my-fancy-token'), new BeforeMiddleware()]);

$dummyAPI->json('GET', '/products');

// Assert we correctly tacked on the Authorization Header
$dummyAPI->assertSent(function (RequestInterface $request) {
    return $request->getHeaderLine('Authorization') === 'Bearer my-fancy-token' &&
        $request->getHeaderLine('X-Custom-Before-Header') === 'Foo';
});
