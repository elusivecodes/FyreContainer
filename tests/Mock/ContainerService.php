<?php
declare(strict_types=1);

namespace Tests\Mock;

use Fyre\Container\Container;

class ContainerService
{
    protected Container $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function getContainer(): Container
    {
        return $this->container;
    }
}
