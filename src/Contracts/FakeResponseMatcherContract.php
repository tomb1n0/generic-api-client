<?php

namespace Tomb1n0\GenericApiClient\Contracts;

use Psr\Http\Message\RequestInterface;

interface FakeResponseMatcherContract
{
    /**
     * Match a request
     *
     * @param RequestInterface $request Due to PSR-7 requests being immutable, we have to pass by reference here
     */
    public function match(RequestInterface $request): bool;
}
