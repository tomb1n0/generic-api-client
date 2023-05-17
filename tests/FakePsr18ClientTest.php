<?php

namespace Tomb1n0\GenericApiClient\Tests;

use Psr\Http\Message\ResponseFactoryInterface;
use Tomb1n0\GenericApiClient\Http\FakeResponse;
use Tomb1n0\GenericApiClient\Tests\BaseTestCase;
use Tomb1n0\GenericApiClient\Matchers\UrlMatcher;
use Tomb1n0\GenericApiClient\Http\FakePsr18Client;
use Tomb1n0\GenericApiClient\Exceptions\NoMatchingStubbedResponseException;

class FakePsr18ClientTest extends BaseTestCase
{
    /** @test */
    public function can_stub_a_response()
    {
        $client = new FakePsr18Client();
        $psr7Response = $this->responseFactory()->createResponse();

        $fakeResponse = $this->mock(FakeResponse::class, function ($mock) use ($psr7Response) {
            $mock
                ->shouldReceive('toPsr7Response')
                ->once()
                ->andReturn($psr7Response);
        });

        $client->stubResponse('https://example.com', $fakeResponse);

        $response = $client->sendRequest($this->requestFactory()->createRequest('GET', 'https://example.com'));

        $this->assertSame($psr7Response, $response);
    }

    /** @test */
    public function can_stub_a_custom_matcher_response()
    {
        $client = new FakePsr18Client();
        $psr7Response = $this->responseFactory()->createResponse();

        $fakeResponse = $this->mock(FakeResponse::class, function ($mock) use ($psr7Response) {
            $mock
                ->shouldReceive('toPsr7Response')
                ->once()
                ->andReturn($psr7Response);
        });

        $client->stubResponseWithCustomMatcher(new UrlMatcher('https://example.com', "GET"), $fakeResponse);

        $response = $client->sendRequest($this->requestFactory()->createRequest('GET', 'https://example.com'));

        $this->assertSame($psr7Response, $response);
    }

    /** @test */
    public function requesting_something_that_has_not_been_stubbed_in_a_custom_matcher_will_throw_an_exception()
    {
        $client = new FakePsr18Client();
        $url = 'https://example.com';
        $fakeResponse = $this->mock(FakeResponse::class);

        $client->stubResponseWithCustomMatcher(new UrlMatcher('https://example.com', "GET"), $fakeResponse);

        try {
            $client->sendRequest($this->requestFactory()->createRequest('POST', 'https://example.com'));

            $this->fail('An exception was not thrown for a non-matching stubbed response');
        } catch (NoMatchingStubbedResponseException $e) {
            $this->assertSame('No stubbed response for ' . $url, $e->getMessage());
        }
    }

    /** @test */
    public function requesting_something_that_has_not_been_stubbed_will_throw_an_exception()
    {
        $client = new FakePsr18Client();
        $url = 'https://foo.com';
        $fakeResponse = $this->mock(FakeResponse::class);

        $client->stubResponse('https://example.com', $fakeResponse);

        try {
            $client->sendRequest($this->requestFactory()->createRequest('GET', $url));

            $this->fail('An exception was not thrown for a non-matching stubbed response');
        } catch (NoMatchingStubbedResponseException $e) {
            $this->assertSame('No stubbed response for ' . $url, $e->getMessage());
        }
    }
}
