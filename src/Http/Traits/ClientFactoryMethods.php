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
        $this->baseUrl = $baseUrl;

        return $this;
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
        $this->paginationHandler = $paginationHandler;

        return $this;
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
        $this->middlewareDispatcher = $this->middlewareDispatcher->withMiddleware($middleware);

        return $this;
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
