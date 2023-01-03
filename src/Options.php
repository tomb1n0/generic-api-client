<?php

namespace Tomb1n0\GenericApiClient;

use Tomb1n0\GenericApiClient\Contracts\MiddlewareContract;
use Tomb1n0\GenericApiClient\Contracts\PaginationHandlerContract;

class Options
{
    /**
     * A BaseURL to use when making requests
     *
     * @var string
     */
    public readonly ?string $baseUrl;

    /**
     * The pagination handler to use when retrieving paginated responses
     *
     * @var PaginationHandlerContract
     */
    public readonly ?PaginationHandlerContract $paginationHandler;

    /**
     * Array of middleware to apply
     *
     * @var array<int, MiddlewareContract>
     */
    public readonly array $middleware;

    public function __construct(
        ?string $baseUrl = null,
        ?PaginationHandlerContract $paginationHandler = null,
        array $middleware = [],
    ) {
        $this->baseUrl = $baseUrl;
        $this->paginationHandler = $paginationHandler;
        $this->middleware = $middleware;
    }
}
