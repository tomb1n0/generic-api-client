<?php

namespace Tomb1n0\GenericApiClient\Matchers;

use Psr\Http\Message\RequestInterface;
use Tomb1n0\GenericApiClient\Contracts\FakeResponseMatcherContract;

class UrlMatcher implements FakeResponseMatcherContract
{
    private ?string $body;

    public function __construct(private string $url, private ?string $method = null, array|string|null $body = null)
    {
        $this->body = is_array($body) ? json_encode($body) : $body;
    }

    /**
     * Match a request
     */
    public function match(RequestInterface $request): bool
    {
        if ($this->requestBodyDifferent($request)) {
            return false;
        }

        if ($this->requestMethodDifferent($request)) {
            return false;
        }

        return $this->requestUrlDifferent($request);
    }

    private function requestUrlDifferent(RequestInterface $request): bool
    {
        $requestUrl = (string) $request->getUri();
        return $this->url === $requestUrl;
    }

    private function requestBodyDifferent(RequestInterface $request): bool
    {
        if (is_null($this->body)) {
            // Skip if no body provided to check
            return false;
        }

        return $this->body != $request->getBody()->getContents();
    }

    private function requestMethodDifferent(RequestInterface $request): bool
    {
        if (is_null($this->method)) {
            // Skip if no method provided to check
            return false;
        }

        return $this->method != $request->getMethod();
    }
}
