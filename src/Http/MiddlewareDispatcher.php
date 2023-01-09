<?php

namespace Tomb1n0\GenericApiClient\Http;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class MiddlewareDispatcher
{
    /**
     * Middleware to be ran.
     *
     * Middleware in the first position will be the first to run and so on
     *
     * @var array<MiddlewareContract>
     */
    protected array $middleware;

    /**
     * Create a new Middleware dispatcher
     *
     * @param array $middleware
     */
    public function __construct(array $middleware = [])
    {
        $this->middleware = is_array($middleware) ? array_reverse($middleware) : [];
    }

    public function withMiddleware(array $middleware = []): static
    {
        $this->middleware = array_reverse($middleware);

        return $this;
    }

    /**
     * Dispatch a callable through the middleware chain
     *
     * @param callable $action The core callable to call at the end of the chain, it must accept a RequestInterface parameter
     * @param RequestInterface $request The request to dispatch through the chain
     * @return ResponseInterface
     */
    public function dispatch(callable $action, RequestInterface $request): ResponseInterface
    {
        foreach ($this->middleware as $middleware) {
            $action = function (RequestInterface $request) use ($middleware, $action) {
                return $middleware->handle($request, $action);
            };
        }

        return $action($request);
    }
}
