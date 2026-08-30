<?php

declare(strict_types=1);

namespace SCTech\Tests\Unit;

use PHPUnit\Framework\TestCase;
use SCTech\Core\Config;
use SCTech\Core\Container;

final class ContainerConfigTest extends TestCase
{
    public function testContainerAutowiresAndHonorsSingletonBindings(): void
    {
        $container = new Container();
        $container->singleton(ExampleDependency::class);

        $first = $container->get(ExampleService::class);
        $second = $container->get(ExampleService::class);

        self::assertNotSame($first, $second);
        self::assertSame($first->dependency, $second->dependency);
    }

    public function testContainerCallUsesNamedValuesAndTypedServices(): void
    {
        $container = new Container();
        $result = $container->call(
            static fn (ExampleDependency $dependency, string $slug): string => $dependency->prefix . $slug,
            ['slug' => 'cloud']
        );

        self::assertSame('service-cloud', $result);
    }

    public function testConfigReadsNestedValuesWithoutConflatingNullAndMissing(): void
    {
        $config = new Config(['app' => ['debug' => false, 'nullable' => null]]);

        self::assertTrue($config->has('app.nullable'));
        self::assertNull($config->get('app.nullable', 'fallback'));
        self::assertFalse($config->bool('app.debug', true));
        self::assertSame('fallback', $config->get('app.missing', 'fallback'));
    }
}
