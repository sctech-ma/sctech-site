<?php

declare(strict_types=1);

namespace SCTech\Middleware;

interface ParameterizedMiddlewareInterface extends MiddlewareInterface
{
    /** @param list<string> $parameters */
    public function withParameters(array $parameters): MiddlewareInterface;
}
