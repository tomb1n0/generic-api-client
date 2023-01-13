<?php

namespace Tomb1n0\GenericApiClient\Http;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Tomb1n0\GenericApiClient\Exceptions\NoMatchingStubbedResponseException;

class FakePsr18Client implements ClientInterface
{
    /**
     * The stubbed responses, keyed by endpoint
     *
     * @var array<string, FakeResponse>
     */
    protected array $stubbedResponses = [];

    public function __construct()
    {
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
