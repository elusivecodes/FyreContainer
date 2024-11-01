<?php
declare(strict_types=1);

namespace Fyre\Container;

/**
 * ContextualAttribute
 */
abstract class ContextualAttribute
{
    /**
     * Resolve a value from the container.
     *
     * @param Container $container The Container.
     * @return mixed The resolved value.
     */
    abstract public function resolve(Container $container): mixed;
}
