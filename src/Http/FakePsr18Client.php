<?php

namespace Tomb1n0\GenericApiClient\Http;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Tomb1n0\GenericApiClient\Contracts\FakeResponseMatcherContract;
use Tomb1n0\GenericApiClient\Exceptions\NoMatchingStubbedResponseException;

class FakePsr18Client implements ClientInterface
{
    /**
     * The stubbed responses, keyed by endpoint
     *
     * @var array<string, FakeResponse>
     */
    protected array $stubs = [];

    public function __construct()
    {
        $this->stubs = [];
    }

    public function stubResponse(FakeResponseMatcherContract $matcher, FakeResponse $fakeResponse): void
    {
        array_push($this->stubs, [
            'matcher' => $matcher,
            'response' => $fakeResponse
        ]);
    }

    private function findMatchingResponse(RequestInterface $request): ?FakeResponse {
        foreach ($this->stubs as $response) {
            $matcher = $response['matcher'];
            if ($matcher->match($request)) {
                return $response['response'];
            }
        }
        return null;
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $response = $this->findMatchingResponse($request);
        if ($response) {
            return $response->toPsr7Response();
        }

        $uri = (string) $request->getUri();
        throw new NoMatchingStubbedResponseException('No stubbed response for ' . $uri);
    }
}
