<?php

namespace Tomb1n0\GenericApiClient\Http\Traits;

use Tomb1n0\GenericApiClient\Contracts\MiddlewareContract;
use Tomb1n0\GenericApiClient\Contracts\PaginationHandlerContract;

trait ClientFactoryMethods
{
    /**
     * Use a Base URL when sending requests.
     *
     * @param string $baseUrl
     * @return static
     */
    public function withBaseUrl(string $baseUrl): static
    {
        $copy = clone $this;
        $copy->baseUrl = $baseUrl;

        return $copy;
    }

    /**
     * Return the configured Base URL.
     *
     * @return string|null
     */
    public function getBaseUrl(): ?string
    {
        return $this->baseUrl;
    }

    /**
     * A pagination handler to handle paged responses.
     *
     * @param PaginationHandlerContract $paginationHandler
     * @return static
     */
    public function withPaginationHandler(PaginationHandlerContract $paginationHandler): static
    {
        $copy = clone $this;
        $copy->paginationHandler = $paginationHandler;

        return $copy;
    }

    /**
     * Return the configured Pagination Handler.
     *
     * @return PaginationHandlerContract|null
     */
    public function getPaginationHandler(): ?PaginationHandlerContract
    {
        return $this->paginationHandler;
    }

    /**
     * Middleware to send the request through
     *
     * @param array<MiddlewareContract> $middleware
     * @return static
     */
    public function withMiddleware(array $middleware): static
    {
        $copy = clone $this;
        $copy->middlewareDispatcher = $this->middlewareDispatcher->withMiddleware($middleware);

        return $copy;
    }

    /**
     * Return the configured middleware
     *
     * @return array
     */
    public function getMiddleware(): array
    {
        return $this->middlewareDispatcher->getMiddleware();
    }
}
