<?php

namespace Tomb1n0\GenericApiClient\Tests\Client;

use GuzzleHttp\Psr7\HttpFactory;
use Psr\Http\Message\RequestInterface;
use Tomb1n0\GenericApiClient\Tests\BaseTestCase;

class SendTest extends BaseTestCase
{
    /** @test */
    public function can_send_a_handcrafted_psr7_request()
    {
        $psr7Request = (new HttpFactory())->createRequest('GET', 'https://example.com');

        $testingClient = $this->createTestingClient();

        $psr7Response = $this->expectSendRequest($testingClient, function (RequestInterface $request) use (
            $psr7Request,
        ) {
            $this->assertSame($psr7Request, $request);

            return true;
        });

        $response = $testingClient->client->send($psr7Request);

        $this->assertSame($psr7Request, $response->toPsr7Request());
        $this->assertSame($psr7Response, $response->toPsr7Response());
    }
}
