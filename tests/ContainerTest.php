<?php
declare(strict_types=1);

namespace Tests;

use Fyre\Container\Container;
use PHPUnit\Framework\TestCase;
use Tests\Mock\ArgumentService;
use Tests\Mock\ContainerService;
use Tests\Mock\InnerService;
use Tests\Mock\InvokableClass;
use Tests\Mock\Item;
use Tests\Mock\ItemContext;
use Tests\Mock\ItemService;
use Tests\Mock\OuterService;
use Tests\Mock\Service;

final class ContainerTest extends TestCase
{
    protected Container $container;

    public function testBuild(): void
    {
        $service = $this->container->build(Service::class);

        $this->assertInstanceOf(Service::class, $service);
    }

    public function testBuildArguments(): void
    {
        $argumentService = $this->container->build(ArgumentService::class, ['a' => 4, 'b' => 5, 'c' => 6]);

        $this->assertInstanceOf(ArgumentService::class, $argumentService);

        $this->assertSame(
            [4, 5, 6],
            $argumentService->getArguments()
        );
    }

    public function testBuildArgumentsDefaults(): void
    {
        $argumentService = $this->container->build(ArgumentService::class, ['b' => 5]);

        $this->assertInstanceOf(ArgumentService::class, $argumentService);

        $this->assertSame(
            [1, 5, 3],
            $argumentService->getArguments()
        );
    }

    public function testBuildContainerDependency(): void
    {
        $containerService = $this->container->build(ContainerService::class);

        $this->assertInstanceOf(ContainerService::class, $containerService);

        $this->assertSame(
            $this->container,
            $containerService->getContainer()
        );
    }

    public function testBuildContext(): void
    {
        $itemService = $this->container->build(ItemService::class);

        $item = $itemService->getItem();

        $this->assertInstanceOf(
            Item::class,
            $item
        );

        $this->assertSame(
            'test',
            $item->getValue()
        );
    }

    public function testBuildContextFromBinding(): void
    {
        $this->container->bindAttribute(ItemContext::class, function(Container $container): Item {
            $this->assertSame(
                $this->container,
                $container
            );

            return new Item('other');
        });

        $itemService = $this->container->build(ItemService::class);

        $item = $itemService->getItem();

        $this->assertInstanceOf(
            Item::class,
            $item
        );

        $this->assertSame(
            'other',
            $item->getValue()
        );
    }

    public function testBuildDependency(): void
    {
        $outerService = $this->container->build(OuterService::class);

        $this->assertInstanceOf(OuterService::class, $outerService);

        $innerService = $outerService->getInnerService();

        $this->assertInstanceOf(InnerService::class, $innerService);

        $this->assertNotSame(
            $innerService,
            $this->container->use(InnerService::class)
        );
    }

    public function testBuildSharedDependency(): void
    {
        $this->container->singleton(InnerService::class);

        $outerService = $this->container->build(OuterService::class);

        $this->assertInstanceOf(OuterService::class, $outerService);

        $innerService = $outerService->getInnerService();

        $this->assertInstanceOf(InnerService::class, $innerService);

        $this->assertSame(
            $innerService,
            $this->container->use(InnerService::class)
        );
    }

    public function testCall(): void
    {
        $this->container->singleton(InnerService::class);

        $ran = false;
        $result = $this->container->call(function(Container $container, OuterService $outerService) use (&$ran): int {
            $ran = true;

            $this->assertSame($this->container, $container);

            $this->assertSame(
                $outerService->getInnerService(),
                $this->container->use(InnerService::class)
            );

            return 3;
        });

        $this->assertTrue($ran);

        $this->assertSame(3, $result);
    }

    public function testCallArguments(): void
    {
        $ran = false;
        $result = $this->container->call(function(int $a = 1, int $b = 2, int $c = 3) use (&$ran): array {
            $ran = true;

            return [$a, $b, $c];
        }, ['a' => 4, 'b' => 5, 'c' => 6]);

        $this->assertTrue($ran);

        $this->assertSame(
            [4, 5, 6],
            $result
        );
    }

    public function testCallArgumentsDependency(): void
    {
        $this->container->singleton(InnerService::class);

        $ran = false;
        $result = $this->container->call(function(Container $container, OuterService $outerService, int $a = 1, int $b = 2, int $c = 3) use (&$ran): array {
            $ran = true;

            $this->assertSame($this->container, $container);

            $this->assertSame(
                $outerService->getInnerService(),
                $this->container->use(InnerService::class)
            );

            return [$a, $b, $c];
        }, ['b' => 5]);

        $this->assertTrue($ran);

        $this->assertSame(
            [1, 5, 3],
            $result
        );
    }

    public function testCallArrayObject(): void
    {
        $result = $this->container->call([new Service(), 'value'], ['a' => 1]);

        $this->assertSame(
            1,
            $result
        );
    }

    public function testCallArrayStatic(): void
    {
        $result = $this->container->call([Service::class, 'staticValue'], ['a' => 1]);

        $this->assertSame(
            1,
            $result
        );
    }

    public function testCallArrayString(): void
    {
        $result = $this->container->call([Service::class, 'value'], ['a' => 1]);

        $this->assertSame(
            1,
            $result
        );
    }

    public function testCallInvokableArrayObject(): void
    {
        $result = $this->container->call([new InvokableClass()], ['a' => 1]);

        $this->assertSame(
            1,
            $result
        );
    }

    public function testCallInvokableArrayString(): void
    {
        $result = $this->container->call([InvokableClass::class], ['a' => 1]);

        $this->assertSame(
            1,
            $result
        );
    }

    public function testCallInvokableClass(): void
    {
        $result = $this->container->call(InvokableClass::class, ['a' => 1]);

        $this->assertSame(
            1,
            $result
        );
    }

    public function testCallInvokableObject(): void
    {
        $result = $this->container->call(new InvokableClass(), ['a' => 1]);

        $this->assertSame(
            1,
            $result
        );
    }

    public function testCallString(): void
    {
        $result = $this->container->call(Service::class.'::value', ['a' => 1]);

        $this->assertSame(
            1,
            $result
        );
    }

    public function testCallStringStatic(): void
    {
        $result = $this->container->call(Service::class.'::staticValue', ['a' => 1]);

        $this->assertSame(
            1,
            $result
        );
    }

    public function testUse(): void
    {
        $service = $this->container->use(Service::class);

        $this->assertInstanceOf(Service::class, $service);

        $this->assertNotSame(
            $service,
            $this->container->use(Service::class)
        );
    }

    public function testUseFactory(): void
    {
        $argumentService = new ArgumentService(7, 8, 9);

        $this->assertSame(
            $this->container,
            $this->container->bind(ArgumentService::class, fn(): ArgumentService => $argumentService)
        );

        $this->assertSame(
            $argumentService,
            $this->container->use(ArgumentService::class)
        );
    }

    public function testUseInstance(): void
    {
        $argumentService = new ArgumentService(7, 8, 9);

        $this->assertSame(
            $argumentService,
            $this->container->instance(ArgumentService::class, $argumentService)
        );

        $this->assertSame(
            $argumentService,
            $this->container->use(ArgumentService::class)
        );
    }

    public function testUseScoped(): void
    {
        $this->assertSame(
            $this->container,
            $this->container->scoped(Service::class)
        );

        $service = $this->container->use(Service::class);

        $this->assertInstanceOf(Service::class, $service);

        $this->assertSame(
            $service,
            $this->container->use(Service::class)
        );

        $this->assertSame(
            $this->container,
            $this->container->clearScoped()
        );

        $this->assertNotSame(
            $service,
            $this->container->use(Service::class)
        );
    }

    public function testUseShared(): void
    {
        $this->assertSame(
            $this->container,
            $this->container->singleton(Service::class)
        );

        $service = $this->container->use(Service::class);

        $this->assertInstanceOf(Service::class, $service);

        $this->assertSame(
            $service,
            $this->container->use(Service::class)
        );
    }

    protected function setUp(): void
    {
        $this->container = new Container();
    }
}
