<?php

namespace Tomb1n0\GenericApiClient\Http;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Tomb1n0\GenericApiClient\Contracts\PaginationHandlerContract;

class Response
{
    /**
     * The Client instance used to create this Response.
     *
     * Useful for paginating as we can create a new request.
     *
     * @var Client
     */
    protected Client $client;

    /**
     * The underlying PSR-7 Request
     *
     * @var RequestInterface
     */
    protected RequestInterface $request;

    /**
     * The underlying PSR-7 Response
     *
     * @var ResponseInterface
     */
    protected ResponseInterface $response;

    /**
     * The contents of the response
     *
     * @var string
     */
    protected string $contents;

    /**
     * The pagination handler. Used when calling the pagination methods.
     *
     * @var PaginationHandlerContract|null
     */
    protected ?PaginationHandlerContract $paginationHandler = null;

    /**
     * The decoded content
     *
     * @var array<mixed>|null
     */
    protected ?array $decoded = null;

    public function __construct(
        Client $client,
        RequestInterface $request,
        ResponseInterface $response,
        ?PaginationHandlerContract $paginationHandler = null,
    ) {
        $this->client = $client;
        $this->request = $request;
        $this->response = $response;
        $this->paginationHandler = $paginationHandler;

        $this->contents = $this->response->getBody()->getContents();
    }

    public function getRequest(): RequestInterface
    {
        return $this->request;
    }

    public function getResponse(): ResponseInterface
    {
        // Ensure the body is rewound so the consumer can read the contents of the body if they want to.
        $this->response->getBody()->rewind();

        return $this->response;
    }

    public function getContents(): string
    {
        return $this->contents;
    }

    // TODO: add support for nested keys wth a dot syntax
    public function json(?string $key = null, mixed $default = null): mixed
    {
        if (!isset($this->decoded)) {
            $this->decoded = json_decode($this->contents, true);
        }

        if (is_null($key)) {
            return $this->decoded;
        }

        return $this->decoded[$key] ?? $default;
    }

    public function status(): int
    {
        return $this->response->getStatusCode();
    }

    public function reason(): string
    {
        return $this->response->getReasonPhrase();
    }

    public function successful(): bool
    {
        return $this->status() >= 200 && $this->status() < 300;
    }

    public function ok(): bool
    {
        return $this->status() === 200;
    }

    public function redirect(): bool
    {
        return $this->status() >= 300 && $this->status() < 400;
    }

    public function unauthorized(): bool
    {
        return $this->status() === 401;
    }

    public function forbidden(): bool
    {
        return $this->status() === 403;
    }

    public function hasNextPage(): bool
    {
        if (isset($this->paginationHandler)) {
            return $this->paginationHandler->hasNextPage($this);
        }

        return false;
    }

    public function getNextPage(): ?Response
    {
        if (isset($this->paginationHandler) && $this->hasNextPage()) {
            $request = $this->paginationHandler->getNextPage($this);

            return $this->client->send($request);
        }

        return null;
    }

    public function forEachPage(callable $callback): void
    {
        if (!isset($this->paginationHandler)) {
            return;
        }

        $response = $this;

        // Call for this first page
        $callback($response);

        // Call for every subsequent page if there is one
        while ($response->hasNextPage()) {
            $response = $response->getNextPage();

            $callback($response);
        }
    }
}
