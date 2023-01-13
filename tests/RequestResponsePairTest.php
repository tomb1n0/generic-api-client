<?php

namespace Tomb1n0\GenericApiClient\Tests;

use Tomb1n0\GenericApiClient\Tests\BaseTestCase;
use Tomb1n0\GenericApiClient\Http\RequestResponsePair;

class RequestResponsePairTest extends BaseTestCase
{
    /** @test */
    public function can_create_a_recorded_request()
    {
        $psr7Request = $this->requestFactory()->createRequest('GET', 'https://example.com');
        $psr7Response = $this->responseFactory()->createResponse();

        $pair = new RequestResponsePair($psr7Request, $psr7Response);

        $this->assertSame($psr7Request, $pair->request);
        $this->assertSame($psr7Response, $pair->response);
    }
}
