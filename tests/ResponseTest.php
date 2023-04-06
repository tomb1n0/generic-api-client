<?php

namespace Tomb1n0\GenericApiClient\Tests;

use Mockery;
use GuzzleHttp\Psr7\Utils;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Tomb1n0\GenericApiClient\Http\Client;
use Tomb1n0\GenericApiClient\Http\Response;
use Tomb1n0\GenericApiClient\Tests\BaseTestCase;
use Tomb1n0\GenericApiClient\Contracts\PaginationHandlerContract;

class ResponseTest extends BaseTestCase
{
    protected function createTestResponse(
        ?Client $client = null,
        ?RequestInterface $request = null,
        ?ResponseInterface $response = null,
        ?PaginationHandlerContract $paginationHandler = null,
    ) {
        return new Response(
            $client ?? $this->mock(Client::class),
            $request ?? $this->requestFactory()->createRequest('GET', 'https://example.com'),
            $response ?? $this->responseFactory()->createResponse(),
            $paginationHandler,
        );
    }

    /** @test */
    public function can_get_the_originating_request()
    {
        $psr7Request = $this->requestFactory()->createRequest('GET', 'https://example.com');

        $response = $this->createTestResponse(request: $psr7Request);

        $this->assertSame($psr7Request, $response->toPsr7Request());
    }

    /** @test */
    public function can_get_the_underlying_response()
    {
        $psr7Response = $this->responseFactory()->createResponse();

        $response = $this->createTestResponse(response: $psr7Response);

        $this->assertSame($psr7Response, $response->toPsr7Response());
    }

    /**
     * @test
     * @dataProvider bodyTypesProvider
     */
    public function can_get_contents(mixed $body, string $expected)
    {
        if (is_array($body)) {
            $body = json_encode($body);
        }

        $psr7Response = $this->responseFactory()->createResponse();
        $psr7Response = $psr7Response->withBody(Utils::streamFor($body));

        $response = $this->createTestResponse(response: $psr7Response);

        $this->assertSame($expected, $response->getContents());
    }

    /** @test */
    public function can_get_json_contents()
    {
        $json = [
            'id' => 1,
            'name' => 'Tom Harper',
        ];

        $psr7Response = $this->responseFactory()
            ->createResponse()
            ->withBody(Utils::streamFor(json_encode($json)));

        $response = $this->createTestResponse(response: $psr7Response);

        $this->assertSame($json, $response->json());
    }

    /** @test */
    public function can_fetch_a_value_out_of_the_json_contents()
    {
        $json = [
            'id' => 1,
            'name' => 'Tom Harper',
        ];

        $psr7Response = $this->responseFactory()
            ->createResponse()
            ->withBody(Utils::streamFor(json_encode($json)));

        $response = $this->createTestResponse(response: $psr7Response);

        $this->assertSame($json['id'], $response->json('id'));
        $this->assertSame($json['name'], $response->json('name'));
    }

    /** @test */
    public function can_fetch_a_nested_value_out_of_the_json_contents()
    {
        $json = [
            'id' => 1,
            'name' => 'Tom Harper',
            'address' => [
                'line_1' => 'Line 1',
                'line_2' => 'Line 2',
            ],
            'roles' => [
                [
                    'name' => 'admin',
                ],
                [
                    'name' => 'user',
                ],
            ],
        ];

        $psr7Response = $this->responseFactory()
            ->createResponse()
            ->withBody(Utils::streamFor(json_encode($json)));

        $response = $this->createTestResponse(response: $psr7Response);

        $this->assertSame($json['address']['line_1'], $response->json('address.line_1'));
        $this->assertSame($json['address']['line_2'], $response->json('address.line_2'));
        $this->assertSame($json['roles'][0]['name'], $response->json('roles.0.name'));
        $this->assertSame($json['roles'][1]['name'], $response->json('roles.1.name'));
    }

    /** @test */
    public function can_provide_a_default_when_fetching_json()
    {
        $json = [
            'id' => 1,
            'name' => 'Tom Harper',
            'address' => [
                'line_1' => 'Line 1',
                'line_2' => 'Line 2',
            ],
            'roles' => [
                [
                    'name' => 'admin',
                ],
                [
                    'name' => 'user',
                ],
            ],
        ];

        $psr7Response = $this->responseFactory()
            ->createResponse()
            ->withBody(Utils::streamFor(json_encode($json)));

        $response = $this->createTestResponse(response: $psr7Response);

        $this->assertSame($json['address']['line_1'], $response->json('address.line_1'));
        $this->assertSame('default', $response->json('address.line_4', 'default'));
        $this->assertSame('default-2', $response->json('doesnt_exist', 'default-2'));
        $this->assertSame(1, $response->json('some.deeply.nested.thing', 1));
        $this->assertSame($json['roles'][0]['name'], $response->json('roles.0.name'));
        $this->assertSame($json['roles'][1]['name'], $response->json('roles.1.name'));
        $this->assertSame('super-admin', $response->json('roles.2.name', 'super-admin'));
    }

    /** @test */
    public function returns_null_if_not_valid_json()
    {
        $json = 'Not JSON';

        $psr7Response = $this->responseFactory()
            ->createResponse()
            ->withBody(Utils::streamFor($json));

        $response = $this->createTestResponse(response: $psr7Response);

        $this->assertNull($response->json());
    }

    /** @test */
    public function can_get_the_status_code()
    {
        $response200 = $this->responseFactory()->createResponse(200);
        $response400 = $this->responseFactory()->createResponse(400);

        $this->assertSame(200, $this->createTestResponse(response: $response200)->status());
        $this->assertSame(400, $this->createTestResponse(response: $response400)->status());
    }

    /** @test */
    public function can_get_reason()
    {
        $response200 = $this->responseFactory()->createResponse(200, 'OK');
        $response400 = $this->responseFactory()->createResponse(400, 'Bad Request');

        $this->assertSame('OK', $this->createTestResponse(response: $response200)->reason());
        $this->assertSame('Bad Request', $this->createTestResponse(response: $response400)->reason());
    }

    /** @test */
    public function returns_successfull_for_a_status_code_between_200_and_300()
    {
        $response199 = $this->responseFactory()->createResponse(199);
        $response200 = $this->responseFactory()->createResponse(200);
        $response299 = $this->responseFactory()->createResponse(299);
        $response300 = $this->responseFactory()->createResponse(300);

        $this->assertFalse($this->createTestResponse(response: $response199)->successful());
        $this->assertTrue($this->createTestResponse(response: $response200)->successful());
        $this->assertTrue($this->createTestResponse(response: $response299)->successful());
        $this->assertFalse($this->createTestResponse(response: $response300)->successful());
    }

    /** @test */
    public function returns_ok_if_status_is_200()
    {
        $response200 = $this->responseFactory()->createResponse(200);
        $response201 = $this->responseFactory()->createResponse(201);

        $this->assertTrue($this->createTestResponse(response: $response200)->ok());
        $this->assertFalse($this->createTestResponse(response: $response201)->ok());
    }

    /** @test */
    public function returns_redirect_if_status_between_300_and_400()
    {
        $response299 = $this->responseFactory()->createResponse(299);
        $response300 = $this->responseFactory()->createResponse(300);
        $response399 = $this->responseFactory()->createResponse(399);
        $response400 = $this->responseFactory()->createResponse(400);

        $this->assertFalse($this->createTestResponse(response: $response299)->redirect());
        $this->assertTrue($this->createTestResponse(response: $response300)->redirect());
        $this->assertTrue($this->createTestResponse(response: $response399)->redirect());
        $this->assertFalse($this->createTestResponse(response: $response400)->redirect());
    }

    /** @test */
    public function returns_unauthorized_if_status_is_401()
    {
        $response401 = $this->responseFactory()->createResponse(401);
        $response403 = $this->responseFactory()->createResponse(403);

        $this->assertTrue($this->createTestResponse(response: $response401)->unauthorized());
        $this->assertFalse($this->createTestResponse(response: $response403)->unauthorized());
    }

    /** @test */
    public function returns_forbidden_if_status_is_403()
    {
        $response403 = $this->responseFactory()->createResponse(403);
        $response401 = $this->responseFactory()->createResponse(401);

        $this->assertFalse($this->createTestResponse(response: $response401)->forbidden());
        $this->assertTrue($this->createTestResponse(response: $response403)->forbidden());
    }

    /** @test */
    public function returns_not_found_if_status_is_404()
    {
        $response404 = $this->responseFactory()->createResponse(404);
        $response401 = $this->responseFactory()->createResponse(401);

        $this->assertFalse($this->createTestResponse(response: $response401)->notFound());
        $this->assertTrue($this->createTestResponse(response: $response404)->notFound());
    }

    /** @test */
    public function returns_false_for_has_next_page_if_no_pagination_handler()
    {
        $this->assertFalse($this->createTestResponse()->hasNextPage());
    }

    /** @test */
    public function returns_null_for_get_next_page_if_no_pagination_handler()
    {
        $this->assertNull($this->createTestResponse()->getNextPage());
    }

    /** @test */
    public function for_each_page_returns_null_if_no_pagination_handler()
    {
        $this->assertNull($this->createTestResponse()->forEachPage(function () {}));
    }

    /** @test */
    public function defers_to_the_pagination_handler_to_determine_next_page_status()
    {
        $paginationHandler = $this->mock(PaginationHandlerContract::class);

        $response = $this->createTestResponse(paginationHandler: $paginationHandler);

        $paginationHandler
            ->shouldReceive('hasNextPage')
            ->once()
            ->with($response)
            ->andReturn(true);

        $this->assertTrue($response->hasNextPage());
    }

    /** @test */
    public function defers_to_the_pagination_handler_to_return_a_request_to_fetch_the_next_page()
    {
        $client = $this->mock(Client::class);
        $paginationHandler = $this->mock(PaginationHandlerContract::class);

        $response = $this->createTestResponse(client: $client, paginationHandler: $paginationHandler);
        $psr7Request = $this->requestFactory()->createRequest('GET', 'https://example.com');
        $psr7Response = $this->responseFactory()->createResponse(200);

        $paginationHandler
            ->shouldReceive('hasNextPage')
            ->once()
            ->with($response)
            ->andReturn(true);

        $paginationHandler
            ->shouldReceive('getNextPage')
            ->once()
            ->andReturn($psr7Request);

        $client
            ->shouldReceive('send')
            ->once()
            ->with($psr7Request)
            ->andReturn($this->createTestResponse(client: $client, response: $psr7Response));

        $response = $response->getNextPage();

        $this->assertTrue($response->successful());
    }

    /** @test */
    public function will_not_defer_to_fetch_next_page_if_there_isnt_one()
    {
        $client = $this->mock(Client::class);
        $paginationHandler = $this->mock(PaginationHandlerContract::class);

        $response = $this->createTestResponse(client: $client, paginationHandler: $paginationHandler);
        $psr7Request = $this->requestFactory()->createRequest('GET', 'https://example.com');
        $psr7Response = $this->responseFactory()->createResponse(200);

        $paginationHandler
            ->shouldReceive('hasNextPage')
            ->once()
            ->with($response)
            ->andReturn(false);

        $paginationHandler->shouldNotReceive('getNextPage');
        $client->shouldNotReceive('send');

        $response = $response->getNextPage();

        $this->assertNull($response);
    }

    /** @test */
    public function can_loop_over_the_pages_using_the_pagination_handler()
    {
        $client = $this->mock(Client::class);
        $paginationHandler = $this->mock(PaginationHandlerContract::class);

        $firstPageResponse = $this->createTestResponse(client: $client, paginationHandler: $paginationHandler);

        $secondPagePsr7Request = $this->requestFactory()->createRequest('GET', 'https://example.com?page=2');
        $secondPagePsr7Response = $this->responseFactory()->createResponse();
        $secondPageResponse = $this->createTestResponse(
            client: $client,
            paginationHandler: $paginationHandler,
            request: $secondPagePsr7Request,
            response: $secondPagePsr7Response,
        );

        // First Page
        $paginationHandler
            ->shouldReceive('hasNextPage')
            ->with($firstPageResponse)
            ->andReturn(true);

        $paginationHandler
            ->shouldReceive('getNextPage')
            ->once()
            ->with($firstPageResponse)
            ->andReturn($secondPagePsr7Request);

        $client
            ->shouldReceive('send')
            ->once()
            ->with($secondPagePsr7Request)
            ->andReturn($secondPageResponse);

        // Second Page
        $paginationHandler
            ->shouldReceive('hasNextPage')
            ->with($secondPageResponse)
            ->andReturn(false);

        $responsesHandled = [];

        $firstPageResponse->forEachPage(function (Response $response) use (&$responsesHandled) {
            $responsesHandled[] = $response;
        });

        $this->assertCount(2, $responsesHandled);
        $this->assertSame($firstPageResponse, $responsesHandled[0]);
        $this->assertSame($secondPageResponse, $responsesHandled[1]);
    }
}
