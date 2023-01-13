<?php

namespace Tomb1n0\GenericApiClient\Http;

use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\ResponseFactoryInterface;

class FakeResponse
{
    /**
     * The body of the response
     *
     * @var mixed
     */
    protected mixed $body = null;

    /**
     * The Status of the Response
     *
     * @var integer
     */
    protected int $status;

    /**
     * The headers of the Response
     *
     * @var array<string, string>
     */
    protected array $headers;

    /**
     * The factory to use to create the respones
     *
     * @var ResponseFactoryInterface
     */
    protected ResponseFactoryInterface $responseFactory;

    /**
     * THe factory to use to create the body of the response
     *
     * @var StreamFactoryInterface
     */
    protected StreamFactoryInterface $streamFactory;

    /**
     * The Stream for our Body.
     *
     * @var StreamInterface
     */
    protected ?StreamInterface $bodyStream = null;

    /**
     * Create a new FakeResponse
     *
     * @param mixed $body
     * @param integer $status
     * @param array<string, string> $headers
     */
    public function __construct(
        ResponseFactoryInterface $responseFactory,
        StreamFactoryInterface $streamFactory,
        mixed $body = null,
        int $status = 200,
        array $headers = [],
    ) {
        $this->responseFactory = $responseFactory;
        $this->streamFactory = $streamFactory;
        $this->body = $body;
        $this->status = $status;
        $this->headers = $headers;

        if (is_array($this->body)) {
            $this->body = json_encode($body);
        }

        if (is_string($this->body)) {
            $this->bodyStream = $this->streamFactory->createStream($this->body);
        }

        if (is_resource($this->body)) {
            $this->bodyStream = $this->streamFactory->createStreamFromResource($this->body);
        }
    }

    public function toPsr7Response(): ResponseInterface
    {
        $response = $this->responseFactory->createResponse($this->status);

        if ($this->bodyStream) {
            $response = $response->withBody($this->bodyStream);
        }

        foreach ($this->headers as $key => $value) {
            $response = $response->withHeader($key, $value);
        }

        return $response;
    }
}
