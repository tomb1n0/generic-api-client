<?php

namespace Tomb1n0\GenericApiClient\Facades;

use Illuminate\Support\Facades\Facade;
use Tomb1n0\GenericApiClient\Http\Client;

/**
 * A Facade for use in a Laravel environment.
 */
class GenericAPI extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return Client::class;
    }
}
