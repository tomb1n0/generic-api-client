<?php

namespace Tomb1n0\GenericApiClient\Http;

use GuzzleHttp\Psr7\Utils;
use GuzzleHttp\Psr7\HttpFactory;
use Psr\Http\Message\ResponseFactoryInterface;

class FakeResponse
{
    protected array|string|null $body = null;
    protected int $status;
    protected array $headers;

    protected ResponseFactoryInterface $responseFactory;

    public function __construct(array|string|null $body = null, int $status = 200, array $headers = [])
    {
        $this->body = $body;
        $this->status = $status;
        $this->headers = $headers;
        $this->responseFactory = new HttpFactory();

        if (is_array($this->body)) {
            $this->body = json_encode($body);
        }
    }

    public function toPsr7Response()
    {
        $response = $this->responseFactory->createResponse($this->status);
        $response = $response->withBody(Utils::streamFor($this->body));

        return $response;
    }
}
