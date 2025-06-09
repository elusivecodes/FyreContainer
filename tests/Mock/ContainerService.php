<?php
declare(strict_types=1);

namespace Tests\Mock;

use Fyre\Container\Container;

class ContainerService
{
    public function __construct(
        protected Container $container
    ) {}

    public function getContainer(): Container
    {
        return $this->container;
    }
}
