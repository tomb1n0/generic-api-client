<?php

namespace Tomb1n0\GenericApiClient\Matchers;

use Psr\Http\Message\RequestInterface;

class SequencedUrlMatcher extends UrlMatcher
{
    private bool $matched = false;

    /**
     * Match a request if has not been matched already
     */
    public function match(RequestInterface $request): bool
    {
        if ($this->hasAlreadyBeenMatched()) {
            return false;
        }

        if (parent::match($request)) {
            $this->markAsMatched();
            return true;
        }

        return false;
    }

    private function hasAlreadyBeenMatched()
    {
        return $this->matched;
    }

    private function markAsMatched()
    {
        return $this->matched = true;
    }
}
