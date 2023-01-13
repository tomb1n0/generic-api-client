<?php

namespace Tomb1n0\GenericApiClient\Tests;

use Tomb1n0\GenericApiClient\Http\FakeResponse;
use Tomb1n0\GenericApiClient\Tests\BaseTestCase;

class FakeResponseTest extends BaseTestCase
{
    /**
     * @test
     * @dataProvider bodyTypesProvider
     */
    public function can_create_a_fake_response_with_various_body_types(mixed $body, string $expected)
    {
        $fakeResponse = new FakeResponse($this->responseFactory(), $this->streamFactory(), $body, 200, []);

        $psr7Response = $fakeResponse->toPsr7Response();

        $this->assertSame($expected, $psr7Response->getBody()->getContents());
        $this->assertSame(200, $psr7Response->getStatusCode());
        $this->assertSame([], $psr7Response->getHeaders());
    }

    /** @test */
    public function can_pass_status_code()
    {
        $fakeResponse = new FakeResponse($this->responseFactory(), $this->streamFactory(), null, 404);

        $psr7Response = $fakeResponse->toPsr7Response();

        $this->assertSame('', $psr7Response->getBody()->getContents());
        $this->assertSame(404, $psr7Response->getStatusCode());
        $this->assertSame([], $psr7Response->getHeaders());
    }

    /** @test */
    public function can_pass_custom_headers()
    {
        $fakeResponse = new FakeResponse($this->responseFactory(), $this->streamFactory(), null, 404, [
            'X-Custom-Header' => 'Custom Header',
            'X-Custom-Another' => 'Another Custom Header',
        ]);

        $psr7Response = $fakeResponse->toPsr7Response();

        $this->assertSame('', $psr7Response->getBody()->getContents());
        $this->assertSame(404, $psr7Response->getStatusCode());
        $this->assertSame(
            [
                'X-Custom-Header' => ['Custom Header'],
                'X-Custom-Another' => ['Another Custom Header'],
            ],
            $psr7Response->getHeaders(),
        );
    }
}
