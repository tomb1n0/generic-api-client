<?php

namespace Tomb1n0\GenericApiClient\Contracts;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

interface MiddlewareContract
{
    /**
     * Handle the middleware.
     *
     * Must return a Response via $next($request) or a brand new Response
     *
     * @param RequestInterface $request Due to PSR-7 requests being immutable, we have to pass by reference here
     * @param Callable $next
     * @return ResponseInterface
     */
    public function handle(RequestInterface $request, callable $next): ResponseInterface;
}
