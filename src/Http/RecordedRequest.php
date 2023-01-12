<?php

namespace Tomb1n0\GenericApiClient\Http;

use Psr\Http\Message\RequestInterface;

class RecordedRequest
{
    public readonly RequestInterface $request;
    public readonly Response $response;

    public function __construct(RequestInterface $request, Response $response)
    {
        $this->request = $request;
        $this->response = $response;
    }
}
