<?php

namespace Tomb1n0\GenericApiClient\Matchers;

use Psr\Http\Message\RequestInterface;
use Tomb1n0\GenericApiClient\Contracts\FakeResponseMatcherContract;

class UrlMatcher implements FakeResponseMatcherContract
{
    public function __construct(private string $url, private ?string $method = null, private ?string $body = null)
    {
    }

    /**
     * Match a request
     */
    public function match(RequestInterface $request): bool
    {
        if (!is_null($this->body)) {
            if ($request->getBody()->getContents() !== $this->body) {
                return false;
            }
        }

        if (!is_null($this->method)) {
            if ($request->getMethod() !== $this->method) {
                return false;
            }
        }

        $requestUrl = (string) $request->getUri();
        return $this->url === $requestUrl;
    }
}
