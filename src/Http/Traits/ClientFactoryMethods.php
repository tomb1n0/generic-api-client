<?php

namespace Tomb1n0\GenericApiClient\Http\Traits;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Tomb1n0\GenericApiClient\Contracts\MiddlewareContract;
use Tomb1n0\GenericApiClient\Contracts\PaginationHandlerContract;

trait ClientFactoryMethods
{
    /**
     * Use a custom PSR-18 Client when sending requests.
     *
     * @param ClientInterface $client
     * @return static
     */
    public function withPsr18Client(ClientInterface $client): static
    {
        $this->client = $client;

        return $this;
    }

    /**
     * Use a Custom PSR-17 request factory when creating requests.
     *
     * @param RequestFactoryInterface $requestFactory
     * @return static
     */
    public function withRequestFactory(RequestFactoryInterface $requestFactory): static
    {
        $this->requestFactory = $requestFactory;

        return $this;
    }

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
     * Middleware to send the request through
     *
     * @param array<MiddlewareContract> $middleware
     * @return static
     */
    public function withMiddleware(array $middleware): static
    {
        $this->middlewareDispatcher->withMiddleware($middleware);

        return $this;
    }
}
