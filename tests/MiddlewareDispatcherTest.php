<?php

namespace Tomb1n0\GenericApiClient\Tests;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use PHPUnit\Framework\Assert as PHPUnit;
use Tomb1n0\GenericApiClient\Tests\BaseTestCase;
use Tomb1n0\GenericApiClient\Http\MiddlewareDispatcher;
use Tomb1n0\GenericApiClient\Contracts\MiddlewareContract;

class MiddlewareTracker
{
    static $calledOrder = [];

    public static function trackCall(string $middlewareClass)
    {
        static::$calledOrder[] = $middlewareClass;
    }
}

abstract class BaseMiddleware implements MiddlewareContract
{
    public function assertHit()
    {
        PHPUnit::assertContains(static::class, MiddlewareTracker::$calledOrder);
    }

    abstract public function handle(RequestInterface $request, callable $next): ResponseInterface;
}

class BeforeMiddleware extends BaseMiddleware
{
    public function handle(RequestInterface $request, callable $next): ResponseInterface
    {
        MiddlewareTracker::trackCall(BeforeMiddleware::class);

        $request = $request->withHeader('X-Custom-Request-Header', 'custom-request-header');

        return $next($request);
    }
}

class AfterMiddleware extends BaseMiddleware
{
    public function handle(RequestInterface $request, callable $next): ResponseInterface
    {
        MiddlewareTracker::trackCall(AfterMiddleware::class);

        $response = $next($request);

        return $response->withHeader('X-Custom-Response-Header', 'custom-response-header');
    }
}

class MiddlewareDispatcherTest extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        MiddlewareTracker::$calledOrder = [];
    }

    /** @test */
    public function can_retrieve_the_middleware()
    {
        $middleware = [new BeforeMiddleware(), new AfterMiddleware()];
        $dispatcher = new MiddlewareDispatcher($middleware);

        $this->assertSame(array_reverse($middleware), $dispatcher->getMiddleware());
    }

    /** @test */
    public function changing_the_middleware_returns_a_new_instance()
    {
        $middleware = [new BeforeMiddleware(), new AfterMiddleware()];
        $dispatcher = new MiddlewareDispatcher();
        $newDispatcher = $dispatcher->withMiddleware($middleware);

        $this->assertNotsame($dispatcher, $newDispatcher);
    }

    /** @test */
    public function can_dispatch_a_core_action_through_the_middleware()
    {
        $beforeMiddleware = new BeforeMiddleware();
        $afterMiddleware = new AfterMiddleware();
        $dispatcher = new MiddlewareDispatcher([$beforeMiddleware, $afterMiddleware]);
        $hitCoreAction = false;

        $request = $this->requestFactory()->createRequest('GET', 'https://example.com');
        $response = $this->responseFactory()->createResponse();

        $pair = $dispatcher->dispatch(function () use ($response, &$hitCoreAction): ResponseInterface {
            $hitCoreAction = true;
            return $response;
        }, $request);

        $this->assertTrue($hitCoreAction);
        $beforeMiddleware->assertHit();
        $afterMiddleware->assertHit();

        $afterMiddlewareRequest = $pair->request;
        $afterMiddlewareResponse = $pair->response;

        $this->assertSame('custom-request-header', $afterMiddlewareRequest->getHeaderLine('X-Custom-Request-Header'));
        $this->assertSame(
            'custom-response-header',
            $afterMiddlewareResponse->getHeaderLine('X-Custom-Response-Header'),
        );
    }

    /** @test */
    public function can_dispatch_a_core_action_through_the_middleware_when_using_with_middleware()
    {
        $beforeMiddleware = new BeforeMiddleware();
        $afterMiddleware = new AfterMiddleware();
        $dispatcher = new MiddlewareDispatcher();
        $dispatcher = $dispatcher->withMiddleware([$beforeMiddleware, $afterMiddleware]);
        $hitCoreAction = false;

        $request = $this->requestFactory()->createRequest('GET', 'https://example.com');
        $response = $this->responseFactory()->createResponse();

        $pair = $dispatcher->dispatch(function () use ($response, &$hitCoreAction): ResponseInterface {
            $hitCoreAction = true;
            return $response;
        }, $request);

        $this->assertTrue($hitCoreAction);
        $beforeMiddleware->assertHit();
        $afterMiddleware->assertHit();

        $afterMiddlewareRequest = $pair->request;
        $afterMiddlewareResponse = $pair->response;

        $this->assertSame('custom-request-header', $afterMiddlewareRequest->getHeaderLine('X-Custom-Request-Header'));
        $this->assertSame(
            'custom-response-header',
            $afterMiddlewareResponse->getHeaderLine('X-Custom-Response-Header'),
        );
    }

    /** @test */
    public function middleware_are_called_in_order_passed_in()
    {
        $beforeMiddleware = new BeforeMiddleware();
        $afterMiddleware = new AfterMiddleware();
        $dispatcher = new MiddlewareDispatcher();
        $dispatcher = $dispatcher->withMiddleware([$beforeMiddleware, $afterMiddleware]);

        $request = $this->requestFactory()->createRequest('GET', 'https://example.com');
        $response = $this->responseFactory()->createResponse();

        $dispatcher->dispatch(function () use ($response, &$hitCoreAction): ResponseInterface {
            return $response;
        }, $request);

        $this->assertSame([BeforeMiddleware::class, AfterMiddleware::class], MiddlewareTracker::$calledOrder);
    }
}
