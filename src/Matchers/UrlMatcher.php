<?php

namespace Tomb1n0\GenericApiClient\Matchers;

use Psr\Http\Message\RequestInterface;
use Tomb1n0\GenericApiClient\Contracts\FakeResponseMatcherContract;

class UrlMatcher implements FakeResponseMatcherContract
{
    public function __construct(private string $url, private ?string $method = "GET")
    {
    }

    /**
     * Match a request to fake a response to
     * @param RequestInterface $request Due to PSR-7 requests being immutable, we have to pass by reference here
     */
    public function match(RequestInterface $request): bool {
        $requestUrl = (string) $request->getUri();
        $requestMethod = $request->getMethod();

        return $this->url === $requestUrl && $this->method === $requestMethod;
    }
}
