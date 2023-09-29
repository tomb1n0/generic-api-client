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

        $this->contents = (string) $this->response->getBody();

        /**
         * Now we've fetched the contents, rewind the stream to ensure anyone fetching the PSR-7 response
         * can read the body correctly without having to rewind first.
         */
        $this->response->getBody()->rewind();
    }

    public function toPsr7Request(): RequestInterface
    {
        return $this->request;
    }

    public function toPsr7Response(): ResponseInterface
    {
        return $this->response;
    }

    public function getContents(): string
    {
        return $this->contents;
    }

    public function json(?string $key = null, mixed $default = null): mixed
    {
        if (!isset($this->decoded)) {
            $this->decoded = json_decode($this->contents, true);
        }

        if (is_null($key)) {
            return $this->decoded;
        }

        if (!str_contains($key, '.')) {
            return $this->decoded[$key] ?? $default;
        }

        $array = $this->decoded;

        foreach (explode('.', $key) as $part) {
            $array = $array[$part] ?? $default;
        }

        return $array;
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

    public function notFound(): bool
    {
        return $this->status() === 404;
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
