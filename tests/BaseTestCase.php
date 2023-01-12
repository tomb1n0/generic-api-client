<?php

namespace Tomb1n0\GenericApiClient\Tests;

use Closure;
use Mockery;
use PHPUnit\Framework\TestCase;

class BaseTestCase extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
    }

    /**
     * Mock the given class, passing it to the given closure
     *
     * @param class-string<T> $classToMock
     * @param Closure $callback
     * @return T
     */
    protected function mock(string $classToMock, ?Closure $callback = null)
    {
        $mock = Mockery::mock($classToMock);

        if ($callback) {
            $callback($mock);
        }

        return $mock;
    }
}
