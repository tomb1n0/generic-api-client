<?php

namespace Tomb1n0\GenericApiClient\Http;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Tomb1n0\GenericApiClient\Matchers\UrlMatcher;
use Tomb1n0\GenericApiClient\Contracts\FakeResponseMatcherContract;
use Tomb1n0\GenericApiClient\Exceptions\NoMatchingStubbedResponseException;

class FakePsr18Client implements ClientInterface
{
    /**
     * Used for generating the default response when not preventing stray requests.
     *
     * @var ResponseFactoryInterface
     */
    protected ResponseFactoryInterface $responseFactory;

    /**
     * The stubbed responses, keyed by endpoint
     *
     * @var array<string, FakeResponse>
     */
    protected array $stubs = [];

    /**
     * Whether we should prevent stray requests.
     *
     * By default, we allow stray requests and just return a 200 OK for convenience.
     *
     * @var boolean
     */
    protected bool $preventStrayRequests = false;

    /**
     * Create a new Fake Client.
     *
     * @param ResponseFactoryInterface $responseFactory
     */
    public function __construct(ResponseFactoryInterface $responseFactory)
    {
        $this->responseFactory = $responseFactory;
        $this->stubs = [];
    }

    public function stubResponse(string $url, FakeResponse $fakeResponse): void
    {
        $this->stubResponseWithCustomMatcher(new UrlMatcher($url), $fakeResponse);
    }

    public function preventStrayRequests(): void
    {
        $this->preventStrayRequests = true;
    }

    public function stubResponseWithCustomMatcher(
        FakeResponseMatcherContract $matcher,
        FakeResponse $fakeResponse,
    ): void {
        array_push($this->stubs, [
            'matcher' => $matcher,
            'response' => $fakeResponse,
        ]);
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $response = $this->findMatchingResponse($request);
        if ($response) {
            return $response->toPsr7Response();
        }

        if ($this->preventStrayRequests) {
            throw new NoMatchingStubbedResponseException(
                'No stubbed response for ' . $request->getMethod() . ' ' . $request->getUri(),
            );
        }

        return $this->responseFactory->createResponse(200);
    }

    private function findMatchingResponse(RequestInterface $request): ?FakeResponse
    {
        foreach ($this->stubs as $response) {
            $matcher = $response['matcher'];
            if ($matcher->match($request)) {
                return $response['response'];
            }
        }
        return null;
    }
}
