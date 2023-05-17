<?php

namespace Tomb1n0\GenericApiClient\Matchers;

use Psr\Http\Message\RequestInterface;
use Tomb1n0\GenericApiClient\Contracts\FakeResponseMatcherContract;

class UrlMatcher implements FakeResponseMatcherContract
{
    public function __construct(private string $url, private ?string $method = 'GET')
    {
    }

    /**
     * Match a request
     */
    public function match(RequestInterface $request): bool
    {
        $requestUrl = (string) $request->getUri();
        $requestMethod = $request->getMethod();

        return $this->url === $requestUrl && $this->method === $requestMethod;
    }
}
