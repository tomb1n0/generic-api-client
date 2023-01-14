<?php

namespace Tomb1n0\GenericApiClient\Tests\Client;

use Psr\Http\Message\RequestInterface;
use Tomb1n0\GenericApiClient\Http\Client;
use Tomb1n0\GenericApiClient\Http\Response;
use Tomb1n0\GenericApiClient\Tests\BaseTestCase;
use Tomb1n0\GenericApiClient\Contracts\PaginationHandlerContract;

class PaginationHandler implements PaginationHandlerContract
{
    /**
     * Determine if the given response has a next page
     *
     * @param Response $response
     * @return boolean
     */
    public function hasNextPage(Response $response): bool
    {
        return $response->toPsr7Response()->hasHeader('next_page');
    }

    /**
     * Create a request that fetches the next page
     *
     * @param Response $response
     * @return RequestInterface
     */
    public function getNextPage(Response $response): RequestInterface
    {
        $originalRequest = $response->toPsr7Request();
        $uri = $originalRequest->getUri();

        $originalQuery = $uri->getQuery();
        $originalQueryArray = [];

        // Parse the query string into an array
        parse_str($originalQuery, $originalQueryArray);

        $nextPageNumber = $response->toPsr7Response()->getHeaderLine('next_page');
        $newUri = $uri->withQuery(
            http_build_query(
                array_merge($originalQueryArray, [
                    'page' => $nextPageNumber,
                ]),
            ),
        );

        return $originalRequest->withUri($newUri);
    }
}

class PaginationTest extends BaseTestCase
{
    /** @test */
    public function not_passing_a_pagination_handler_means_the_response_wont_paginate()
    {
        $testingClient = $this->createTestingClient();

        $client = $testingClient->client
            ->fake()
            ->stubResponse('https://example.com?page=1', ['page' => 1], 200, [
                'page' => 1,
                'next_page' => 2,
            ])
            ->stubResponse('https://example.com?page=2', ['page' => 2], 200, ['page' => 2]);

        $response = $client->json('GET', 'https://example.com', ['page' => 1]);

        $this->assertSame(['page' => 1], $response->json());

        $this->assertFalse($response->hasNextPage());
        $this->assertNull($response->getNextPage());
    }

    /** @test */
    public function can_pass_a_paginator_in_to_handle_pagination()
    {
        $testingClient = $this->createTestingClient(function (Client $client) {
            return $client->withPaginationHandler(new PaginationHandler());
        });

        $client = $testingClient->client
            ->fake()
            ->stubResponse('https://example.com?page=1', ['page' => 1], 200, [
                'page' => 1,
                'next_page' => 2,
            ])
            ->stubResponse('https://example.com?page=2', ['page' => 2], 200, ['page' => 2]);

        $response = $client->json('GET', 'https://example.com', ['page' => 1]);

        $this->assertTrue($response->hasNextPage());
        $this->assertSame(['page' => 1], $response->json());

        $nextPage = $response->getNextPage();

        $this->assertSame(['page' => 2], $nextPage->json());
    }

    /** @test */
    public function can_loop_over_each_page()
    {
        $testingClient = $this->createTestingClient(function (Client $client) {
            return $client->withPaginationHandler(new PaginationHandler());
        });

        $client = $testingClient->client
            ->fake()
            ->stubResponse('https://example.com?page=1', ['page' => 1], 200, ['next_page' => 2])
            ->stubResponse('https://example.com?page=2', ['page' => 2], 200, ['next_page' => 3])
            ->stubResponse('https://example.com?page=3', ['page' => 3], 200, ['next_page' => 4])
            ->stubResponse('https://example.com?page=4', ['page' => 4], 200, ['next_page' => 5])
            ->stubResponse('https://example.com?page=5', ['page' => 5]);

        $page = 1;
        $client
            ->json('GET', 'https://example.com', ['page' => 1])
            ->forEachPage(function (Response $response) use (&$page) {
                $this->assertSame($page, $response->json('page'));

                $page++;
            });
    }
}
