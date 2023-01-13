<?php

namespace Tomb1n0\GenericApiClient\Tests\Mocks;

use Closure;
use Mockery\MockInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\UriFactoryInterface;
use Tomb1n0\GenericApiClient\Http\Client;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseFactoryInterface;

class ClientMock
{
    public readonly Client $client;

    /**
     * @param MockInterface|ClientInterface $psr18Client
     * @param MockInterface|RequestFactoryInterface $psr17RequestFactory
     * @param MockInterface|ResponseFactoryInterface $psr17ResponseFactory
     * @param MockInterface|StreamFactoryInterface $psr7StreamFactory
     * @param MockInterface|UriFactoryInterface $psr7UriFactory
     */
    public function __construct(
        public readonly MockInterface|ClientInterface $psr18Client,
        public readonly MockInterface|RequestFactoryInterface $psr17RequestFactory,
        public readonly MockInterface|ResponseFactoryInterface $psr17ResponseFactory,
        public readonly MockInterface|StreamFactoryInterface $psr7StreamFactory,
        public readonly MockInterface|UriFactoryInterface $psr7UriFactory,
        ?Closure $clientCreationCallback = null,
    ) {
        $client = new Client(
            $psr18Client,
            $psr17RequestFactory,
            $psr17ResponseFactory,
            $psr7StreamFactory,
            $psr7UriFactory,
        );

        /**
         * Allow the consumer to hook into the creation of the client to add any additional configuration.
         */
        if ($clientCreationCallback) {
            $client = $clientCreationCallback($client);
        }

        $this->client = $client;
    }
}
