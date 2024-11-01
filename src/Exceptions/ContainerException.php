<?php
declare(strict_types=1);

namespace Fyre\Container\Exceptions;

use RuntimeException;

/**
 * ContainerException
 */
class ContainerException extends RuntimeException
{
    public static function forInvalidClass(string $className): static
    {
        return new static('Invalid class name: '.$className);
    }

    public static function forNotInstantiableClass(string $className): static
    {
        return new static('Class is not instantiable: '.$className);
    }

    public static function forUnresolvedDependency(string $paramName): static
    {
        return new static('Dependency could not be resolved: '.$paramName);
    }
}
