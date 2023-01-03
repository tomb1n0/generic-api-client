<?php

namespace Tomb1n0\GenericApiClient\Contracts;

use Psr\Http\Message\RequestInterface;
use Tomb1n0\GenericApiClient\Http\Response;

interface PaginationHandlerContract
{
    /**
     * Determine if the given response has a next page
     *
     * @param Response $response
     * @return boolean
     */
    public function hasNextPage(Response $response): bool;

    /**
     * Create a request that fetches the next page
     *
     * @param Response $response
     * @return RequestInterface
     */
    public function getNextPage(Response $response): RequestInterface;
}
