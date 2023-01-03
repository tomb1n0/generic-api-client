<?php

namespace Tomb1n0\GenericApiClient\Http;

use Tomb1n0\GenericApiClient\Options;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class Response
{
    protected Client $client;
    protected RequestInterface $request;
    protected ResponseInterface $response;
    protected Options $options;
    protected string $contents;

    public function __construct(
        Client $client,
        RequestInterface $request,
        ResponseInterface $response,
        Options $options,
    ) {
        $this->client = $client;
        $this->request = $request;
        $this->response = $response;
        $this->options = $options;

        $this->contents = $this->response->getBody()->getContents();
    }

    public function getRequest(): RequestInterface
    {
        return $this->request;
    }

    public function getResponse(): ResponseInterface
    {
        return $this->response;
    }

    public function getContents(): string
    {
        return $this->contents;
    }

    public function getJsonContents(): array
    {
        return json_decode($this->contents, true);
    }

    public function hasNextPage(): bool
    {
        if (isset($this->options->paginationHandler)) {
            return $this->options->paginationHandler->hasNextPage($this);
        }

        return false;
    }

    public function getNextPage(): ?Response
    {
        if (isset($this->options->paginationHandler) && $this->hasNextPage()) {
            $request = $this->options->paginationHandler->getNextPage($this);

            return $this->client->send($request);
        }

        return null;
    }

    public function forEachPage(callable $callback): void
    {
        if (!isset($this->options->paginationHandler)) {
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
