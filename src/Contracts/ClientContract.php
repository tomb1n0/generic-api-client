<?php

namespace Tomb1n0\GenericApiClient\Contracts;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Tomb1n0\GenericApiClient\Http\Response;

interface ClientContract
{
    /**
     * Perform a JSON request
     *
     * @param string $method
     * @param string $url
     * @param array $params
     * @return Response
     */
    public function json(string $method, string $url, array $params = []): Response;

    /**
     * Perform a x-www-form-urlencoded request
     *
     * @param string $method
     * @param string $url
     * @param array $params
     * @return Response
     */
    public function form(string $method, string $url, array $params = []): Response;

    /**
     * Actually send the request.
     *
     * This is useful because you might want to send a handcrafted PSR-7 request instead of relying on json/form methods
     *
     * @param RequestInterface $request
     * @return Response
     */
    public function send(RequestInterface $request): Response;
}
