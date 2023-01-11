<?php

namespace Tomb1n0\GenericApiClient\Http;

use RuntimeException;
use GuzzleHttp\Psr7\HttpFactory;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ResponseFactoryInterface;

class FakePsr18Client implements ClientInterface
{
    protected ResponseFactoryInterface $responseFactory;
    protected array $stubbedResponses = [];

    public function __construct()
    {
        $this->responseFactory = new HttpFactory();
        $this->stubbedResponses = [];
    }

    public function stubResponse(string $url, FakeResponse $fakeResponse)
    {
        $this->stubbedResponses[$url] = $fakeResponse;
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $uri = (string) $request->getUri();

        if (isset($this->stubbedResponses[$uri])) {
            return $this->stubbedResponses[$uri]->toPsr7Response();
        }

        throw new RuntimeException('No stubbed response for ' . $uri);
    }
}
