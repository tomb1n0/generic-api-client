# Generic API Client

[![Latest Version on Packagist](https://img.shields.io/packagist/v/tomb1n0/generic-api-client.svg?style=flat-square)](https://packagist.org/packages/tomb1n0/generic-api-client)
[![Total Downloads](https://img.shields.io/packagist/dt/tomb1n0/generic-api-client.svg?style=flat-square)](https://packagist.org/packages/tomb1n0/generic-api-client)

When developing integrations for my PHP applications, i've often found myself carrying very similar but slightly different boiler-plate code around with me.

Often, each implementation will need:

-   Some way of handling auth.
    -   Often is as simple as adding an extra header or property to the body of the request.
-   Pagination handling
    -   Often boils down to checking for the presence of a header or some property in the body of the response, before fetching the next page with an added header/query parameter.
-   Response Mocking
    -   Providing confidence that our integration is working as we expected, and error conditions are properly handled.

There's also the question of what HTTP client to use, with the introduction of [PSR-7](https://www.php-fig.org/psr/psr-7/), [PSR-17](https://www.php-fig.org/psr/psr-17) and [PSR-18](https://www.php-fig.org/psr/psr-18) we are able to depend on HTTP clients and factories that implement these interfaces rather than relying on any one client.

My goal with this package is to provide a wrapper around these PSR interfaces that makes it simpler to write API integrations.

## Installation

Please note that this package does not require a HTTP client out of the box - but rather it depends on the virtual packages `psr/http-client-implementation` and `psr/http-factory-implementation`. This allows the package to be client-agnostic.

If you're unsure on this, i would recommend requiring `guzzlehttp/guzzle` alongside this package as it provides implementations for the above virtual packages.

```bash
composer require tomb1n0/generic-api-client guzzlehttp/guzzle
```

If your project already require a HTTP-client that has implementations for the above standards, you can omit the guzzle dependency.

## Usage

Please note that the examples below assume the use of Guzzle for the PSR-18 Client etc. Feel free to swap these out with your own.

#### Client Instantiation:

```php
use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Client as GuzzleHttpClient;

$api = new Client(
    new GuzzleHttpClient(), // PSR-18 Client that sends the PSR-7 Request
    new HttpFactory(), // A PSR-17 Request Factory used to create PSR-7 Requests
    new HttpFactory(), // A PSR-17 Response Factory used to create PSR-7 Responses
    new HttpFactory(), // A PSR-17 Stream Factory used to create the bodies of our PSR-7 requests
    new HttpFactory(), // a PSR-17 URI Factory used to create URIs.
);
```

#### Making a JSON request:

```php
$response = $api->json('GET', 'https://dummyjson.com/products');

if ($response->successful()) {
    $products = $response->json('products');
}
```

#### Making a Form (x-www-form-urlencoded) request:

```php
$response = $api->form('GET', 'https://dummyjson.com/products');

if ($response->successful()) {
    $products = $response->json('products');
}
```

#### Making a request using a PSR-7 request directly

```php
$requestFactory = new GuzzleHttp\Psr7\HttpFactory();
$request = $requestFactory->createRequest('GET', 'https://example.com');

$response = $api->send($request);

if ($response->successful()) {
    // Do something with the response.
}
```

### Configuration

#### Base Url:

```php
$client = $existingClient->withBaseUrl('https://dummyjson.com');

$response = $client->json('GET', '/products'); // Will make a request to https://dummyjson.com/products.
```

Note that if you try to perform a request to a fully-formed URL that is different to the Base URL, the Base URL is ignored.

#### Pagination

You can create a pagination handler by creating a class that implements the `PaginationHandlerContract` interface provided by this package.

```php
// Create a class that implements the PaginationHandlerContract
class PaginationHandler implements PaginationHandlerContract
{
    public function hasNextPage(Response $response): bool
    {
        return $response->toPsr7Response()->hasHeader('next-page');
    }

    public function getNextPage(Response $response): RequestInterface
    {
        $originalRequest = $response->toPsr7Request();
        $psr7Response = $response->toPsr7Response();

        return $originalRequest->withHeader('page', $psr7Response->getHeaderLine('next-page'));
    }
}
$handler = new PaginationHandler();
$client = $existingClient->withPaginationHandler($handler);

$response = $client->json('GET', 'https://dummyjson.com/products');

// HasNextPage will defer to the Pagination Handler to determine if the Response has a next page
if ($response->hasNextPage()) {
    $nextPage = $response->getNextPage();
}

// For convenience, a helper is provided to fetch all pages in a loop:
$response->forEachPage(function (Response $response) {
    // Do something with this pages response
});
```

#### Middleware

Middleware can be created by creating a class that implements the `MiddlewareContract` interface.

```php
class AuthenticationMiddleware implements MiddlewareContract
{
    public function __construct(protected string $accessToken)
    {
    }

    public function handle(RequestInterface $request, callable $next): ResponseInterface
    {
        // Mutate the request
        $request = $request->withHeader('Authorization', 'Bearer ' . $this->accessToken);

        // Call the next middleware in the chain, ultimately fetching the Response.
        $response = $next($request);

        // Can also mutate the Response here if desired.
        $response = $response->withHeader('X-My-Header', 'Foo');

        // Return the Response
        return $response;
    }
}

// Multiple middleware can be provided
$client = $existingClient->withMiddleware([
    new AuthenticationMiddleware('my-access-token');
]);

// The request will be sent through our middleware in the order given.
$response = $client->json('GET', 'https://dummyjson.com/products');
```

Note that it is possible for a middleware to mutate the request before it is sent, or the response after it is received.

### Testing the API

#### Stubbing Responses

It is possible to stub responses for testing purposes:

```php
// It is important to call fake first, as this returns a new client with a Fake PSR-18 client underneath.
$client = $existingClient->fake()->stubResponse(
    'https://dummyjson.com/products',
    [
        'products' => [['id' => 1], ['id' => 2]],
    ],
    200,
    ['X-Custom-Header' => 'Foo'],
);

$response = $client->json('GET', 'https://dummyjson.com/products');

if ($response->successful()) {
    $products = $response->json('products');
}
```

Please note that once responses have been stubbed, un-stubbed requests will throw an exception.

#### Asserting Requests

Maybe you want to assert the correct payload is sent to an API to create a user:

```php
$client = $existingClient->fake()->stubResponse('https://dummyjson.com/users', null, 200);

// This would likely be in some Service object method your test is calling.
$response = $client->json('POST', 'https://dummyjson.com/users', ['name' => 'Tom']);

// Assert we sent a request with the correct payload
$client->assertSent(function (RequestInterface $request) {
    $contents = $request->getBody()->getContents();
    $expected = ['name' => 'Tom'];

    return $contents === $expected
});
```

#### Asserting Requests With Custom Request Matching

Maybe you want to stub a response using some other information in the request

Do the same as above but use the `stubResponseWithCustomMatcher` method providing a custom implementation of the matcher contract.
For example you could use the included `UrlMatcher` to check the method type

```php
class UrlMatcher implements FakeResponseMatcherContract
{
    public function __construct(private string $url, private ?string $method = "GET")
    {
    }

    public function match(RequestInterface $request): bool {
        $requestUrl = (string) $request->getUri();
        $requestMethod = $request->getMethod();

        return $this->url === $requestUrl && $this->method === $requestMethod;
    }
}
```

```php
$client = $existingClient->fake();
$client->stubResponseWithCustomMatcher(new UrlMatcher('https://dummyjson.com/users', 'GET'), null, 200);
$client->stubResponseWithCustomMatcher(new UrlMatcher('https://dummyjson.com/users', 'POST'), null, 500);
```

## Running the Tests

```bash
composer test
```

## Credits

-   [Tom Harper](https://github.com/tomb1n0)
-   [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
