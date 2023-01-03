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
     * @param RequestInterface $request
     * @param Callable $next
     * @return ResponseInterface
     */
    public function handle(RequestInterface $request, callable $next): ResponseInterface;
}
