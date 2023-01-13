<?php

namespace Tomb1n0\GenericApiClient\Tests;

use Tomb1n0\GenericApiClient\Http\Client;
use Tomb1n0\GenericApiClient\Http\Response;
use Tomb1n0\GenericApiClient\Tests\BaseTestCase;
use Tomb1n0\GenericApiClient\Http\RecordedRequest;

class RecordedRequestTest extends BaseTestCase
{
    /** @test */
    public function can_create_a_recorded_request()
    {
        $psr7Request = $this->requestFactory()->createRequest('GET', 'https://example.com');
        $psr7Response = $this->responseFactory()->createResponse();
        $response = new Response($this->mock(Client::class), $psr7Request, $psr7Response, null);

        $recordedRequest = new RecordedRequest($psr7Request, $response);

        $this->assertSame($psr7Request, $recordedRequest->request);
        $this->assertSame($response, $recordedRequest->response);
    }
}
