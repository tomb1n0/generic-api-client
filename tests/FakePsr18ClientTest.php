<?php

namespace Tomb1n0\GenericApiClient\Tests;

use GuzzleHttp\Psr7\Utils;
use Tomb1n0\GenericApiClient\Http\FakeResponse;
use Tomb1n0\GenericApiClient\Tests\BaseTestCase;
use Tomb1n0\GenericApiClient\Matchers\UrlMatcher;
use Tomb1n0\GenericApiClient\Http\FakePsr18Client;
use Tomb1n0\GenericApiClient\Matchers\SequencedUrlMatcher;
use Tomb1n0\GenericApiClient\Exceptions\NoMatchingStubbedResponseException;

class FakePsr18ClientTest extends BaseTestCase
{
    /** @test */
    public function can_stub_a_get_response()
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
    public function can_stub_a_post_response()
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

        $response = $client->sendRequest($this->requestFactory()->createRequest('POST', 'https://example.com'));

        $this->assertSame($psr7Response, $response);
    }

    /** @test */
    public function can_ignore_get_request_for_a_post_stub_response()
    {
        $client = new FakePsr18Client();
        $fakeResponse = $this->mock(FakeResponse::class);

        $client->stubResponseWithCustomMatcher(new UrlMatcher('https://example.com', 'POST'), $fakeResponse);

        try {
            $client->sendRequest($this->requestFactory()->createRequest('GET', 'https://example.com'));

            $this->fail('An exception was not thrown for a non-matching stubbed response');
        } catch (NoMatchingStubbedResponseException $e) {
            $this->assertSame('No stubbed response for GET https://example.com', $e->getMessage());
        }
    }

    /** @test */
    public function can_ignore_post_request_for_a_get_stub_response()
    {
        $client = new FakePsr18Client();
        $fakeResponse = $this->mock(FakeResponse::class);

        $client->stubResponseWithCustomMatcher(new UrlMatcher('https://example.com', 'GET'), $fakeResponse);

        try {
            $client->sendRequest($this->requestFactory()->createRequest('POST', 'https://example.com'));

            $this->fail('An exception was not thrown for a non-matching stubbed response');
        } catch (NoMatchingStubbedResponseException $e) {
            $this->assertSame('No stubbed response for POST https://example.com', $e->getMessage());
        }
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

        $client->stubResponseWithCustomMatcher(new UrlMatcher('https://example.com', 'GET'), $fakeResponse);

        $response = $client->sendRequest($this->requestFactory()->createRequest('GET', 'https://example.com'));

        $this->assertSame($psr7Response, $response);
    }

    /** @test */
    public function can_stub_a_custom_matcher_post_response()
    {
        $client = new FakePsr18Client();
        $psr7Response = $this->responseFactory()->createResponse();

        $fakeResponse = $this->mock(FakeResponse::class, function ($mock) use ($psr7Response) {
            $mock
                ->shouldReceive('toPsr7Response')
                ->once()
                ->andReturn($psr7Response);
        });

        $client->stubResponseWithCustomMatcher(new UrlMatcher('https://example.com', 'POST'), $fakeResponse);

        $response = $client->sendRequest($this->requestFactory()->createRequest('POST', 'https://example.com'));

        $this->assertSame($psr7Response, $response);
    }

    /** @test */
    public function can_stub_a_custom_matcher_with_body_matching()
    {
        $client = new FakePsr18Client();
        $psr7Response = $this->responseFactory()->createResponse();

        $fakeResponse = $this->mock(FakeResponse::class, function ($mock) use ($psr7Response) {
            $mock
                ->shouldReceive('toPsr7Response')
                ->once()
                ->andReturn($psr7Response);
        });

        $json = [
            'id' => 1,
        ];

        $client->stubResponseWithCustomMatcher(new UrlMatcher('https://example.com', 'POST', json_encode($json)), $fakeResponse);

        $request = $this->requestFactory()->createRequest('POST', 'https://example.com');
        $request = $request->withBody(Utils::streamFor(json_encode($json)));

        $response = $client->sendRequest($request);

        $this->assertSame($psr7Response, $response);
    }

    /** @test */
    public function can_stub_a_custom_matcher_with_multiple_body_matches()
    {
        $client = new FakePsr18Client();

        $psr7Response1 = $this->responseFactory()->createResponse(200, "First response");
        $psr7Response2 = $this->responseFactory()->createResponse(200, "Second response");

        $fakeResponse1 = $this->mock(FakeResponse::class, function ($mock) use ($psr7Response1) {
            $mock
                ->shouldReceive('toPsr7Response')
                ->once()
                ->andReturn($psr7Response1);
        });
        $fakeResponse2 = $this->mock(FakeResponse::class, function ($mock) use ($psr7Response2) {
            $mock
                ->shouldReceive('toPsr7Response')
                ->once()
                ->andReturn($psr7Response2);
        });

        $client->stubResponseWithCustomMatcher(new SequencedUrlMatcher('https://example.com', 'POST', json_encode([ 'id' => 1 ])), $fakeResponse1);
        $client->stubResponseWithCustomMatcher(new SequencedUrlMatcher('https://example.com', 'POST', json_encode([ 'id' => 2 ])), $fakeResponse2);

        $request1 = $this->requestFactory()->createRequest('POST', 'https://example.com');
        $request1 = $request1->withBody(Utils::streamFor(json_encode([ 'id' => 1 ])));

        $request2 = $this->requestFactory()->createRequest('POST', 'https://example.com');
        $request2 = $request2->withBody(Utils::streamFor(json_encode([ 'id' => 2 ])));

        $response1 = $client->sendRequest($request1);
        $response2 = $client->sendRequest($request2);

        $this->assertSame($psr7Response1, $response1);
        $this->assertSame($psr7Response2, $response2);
    }

    /** @test */
    public function can_stub_a_custom_matcher_with_body_not_matched()
    {
        $client = new FakePsr18Client();

        $fakeResponse = $this->mock(FakeResponse::class, function ($mock) {
            $mock
                ->shouldReceive('toPsr7Response')
                ->never();
        });

        $mockedJsonAsString = json_encode([
            'id' => 2,
        ]);
        $jsonAsString = json_encode([
            'id' => 1,
        ]);

        $client->stubResponseWithCustomMatcher(new UrlMatcher('https://example.com', 'POST', $mockedJsonAsString), $fakeResponse);

        try {
            $request = $this->requestFactory()->createRequest('POST', 'https://example.com')->withBody(Utils::streamFor($jsonAsString));
            $response = $client->sendRequest($request);

            $this->fail('An exception was not thrown for a non-matching stubbed response');
        } catch (NoMatchingStubbedResponseException $e) {
            $this->assertSame('No stubbed response for POST https://example.com', $e->getMessage());
        }
    }

    /** @test */
    public function will_return_multiple_different_responses_for_the_same_match_with_a_sequenced_matcher()
    {
        $client = new FakePsr18Client();

        $expectedResponse1 = $this->responseFactory()->createResponse(200, 'First response');
        $expectedResponse2 = $this->responseFactory()->createResponse(200, 'Second response');

        $fakeResponse1 = $this->mock(FakeResponse::class, function ($mock) use ($expectedResponse1) {
            $mock
                ->shouldReceive('toPsr7Response')
                ->once()
                ->andReturn($expectedResponse1);
        });
        $fakeResponse2 = $this->mock(FakeResponse::class, function ($mock) use ($expectedResponse2) {
            $mock
                ->shouldReceive('toPsr7Response')
                ->once()
                ->andReturn($expectedResponse2);
        });

        $client->stubResponseWithCustomMatcher(new SequencedUrlMatcher('https://example.com', 'GET'), $fakeResponse1);
        $client->stubResponseWithCustomMatcher(new SequencedUrlMatcher('https://example.com', 'GET'), $fakeResponse2);

        $actualResponse1 = $client->sendRequest($this->requestFactory()->createRequest('GET', 'https://example.com'));
        $actualResponse2 = $client->sendRequest($this->requestFactory()->createRequest('GET', 'https://example.com'));

        $this->assertSame($expectedResponse1, $actualResponse1);
        $this->assertSame($expectedResponse2, $actualResponse2);
    }

    /** @test */
    public function will_always_return_the_same_response_for_a_match_by_default()
    {
        $client = new FakePsr18Client();

        $expectedResponse1 = $this->responseFactory()->createResponse(200, 'First response');

        $fakeResponse1 = $this->mock(FakeResponse::class, function ($mock) use ($expectedResponse1) {
            $mock
                ->shouldReceive('toPsr7Response')
                ->twice()
                ->andReturn($expectedResponse1);
        });
        $otherFakeResponse2 = $this->mock(FakeResponse::class, function ($mock) {
            $mock->shouldReceive('toPsr7Response')->never();
        });

        $client->stubResponseWithCustomMatcher(new UrlMatcher('https://example.com', 'GET'), $fakeResponse1);
        $client->stubResponseWithCustomMatcher(new UrlMatcher('https://example.com', 'GET'), $otherFakeResponse2);

        $actualResponse1 = $client->sendRequest($this->requestFactory()->createRequest('GET', 'https://example.com'));
        $actualResponse2 = $client->sendRequest($this->requestFactory()->createRequest('GET', 'https://example.com'));

        $this->assertSame($expectedResponse1, $actualResponse1);
        $this->assertSame($expectedResponse1, $actualResponse2);
    }

    /** @test */
    public function requesting_something_that_has_not_been_stubbed_in_a_custom_matcher_will_throw_an_exception()
    {
        $client = new FakePsr18Client();
        $fakeResponse = $this->mock(FakeResponse::class);

        $client->stubResponseWithCustomMatcher(new UrlMatcher('https://example.com', 'GET'), $fakeResponse);

        try {
            $client->sendRequest($this->requestFactory()->createRequest('POST', 'https://example.com'));

            $this->fail('An exception was not thrown for a non-matching stubbed response');
        } catch (NoMatchingStubbedResponseException $e) {
            $this->assertSame('No stubbed response for POST https://example.com', $e->getMessage());
        }
    }

    /** @test */
    public function requesting_something_that_has_not_been_stubbed_will_throw_an_exception()
    {
        $client = new FakePsr18Client();
        $fakeResponse = $this->mock(FakeResponse::class);

        $client->stubResponse('https://example.com', $fakeResponse);

        try {
            $client->sendRequest($this->requestFactory()->createRequest('GET', 'https://foo.com'));

            $this->fail('An exception was not thrown for a non-matching stubbed response');
        } catch (NoMatchingStubbedResponseException $e) {
            $this->assertSame('No stubbed response for GET https://foo.com', $e->getMessage());
        }
    }
}
