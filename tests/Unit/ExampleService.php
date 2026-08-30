<?php

declare(strict_types=1);

namespace SCTech\Tests\Unit;

final class ExampleService
{
    public function __construct(public readonly ExampleDependency $dependency)
    {
    }
}
