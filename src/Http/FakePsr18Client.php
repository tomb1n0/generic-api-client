<?php

namespace Tomb1n0\GenericApiClient\Http;

use RuntimeException;
use GuzzleHttp\Psr7\HttpFactory;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Tomb1n0\GenericApiClient\Exceptions\NoMatchingStubbedResponseException;

class FakePsr18Client implements ClientInterface
{
    /**
     * The response factory to use when creating PSR-7 Response
     *
     * @var ResponseFactoryInterface
     */
    protected ResponseFactoryInterface $responseFactory;

    /**
     * The stubbed responses, keyed by endpoint
     *
     * @var array<string, FakeResponse>
     */
    protected array $stubbedResponses = [];

    public function __construct(ResponseFactoryInterface $responseFactory)
    {
        $this->responseFactory = $responseFactory;
        $this->stubbedResponses = [];
    }

    public function stubResponse(string $url, FakeResponse $fakeResponse): void
    {
        $this->stubbedResponses[$url] = $fakeResponse;
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $uri = (string) $request->getUri();

        // Naive approach for now, in the future we might want to stub GET vs POST requests differently, support regexing etc
        if (isset($this->stubbedResponses[$uri])) {
            return $this->stubbedResponses[$uri]->toPsr7Response();
        }

        throw new NoMatchingStubbedResponseException('No stubbed response for ' . $uri);
    }
}
