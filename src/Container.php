<?php
declare(strict_types=1);

namespace Fyre\Container;

use Closure;
use Fyre\Container\Exceptions\ContainerException;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionNamedType;
use Throwable;

use function array_key_exists;
use function array_merge;
use function array_search;
use function array_values;
use function class_exists;
use function explode;
use function is_array;
use function is_string;
use function str_contains;

/**
 * Container
 */
class Container
{
    protected static Container $instance;

    protected array $bindings = [];

    protected array $contextualAttributes = [];

    protected array $dependencyMap = [];

    protected array $instances = [];

    protected array $scoped = [];

    /**
     * Get the global instance.
     *
     * @return static The Container.
     */
    public static function getInstance(): static
    {
        return static::$instance ??= new static();
    }

    /**
     * Set the global instance.
     *
     * @param Container $instance The Container.
     */
    public static function setInstance(Container $instance): void
    {
        static::$instance = $instance;
    }

    /**
     * New Container constructor.
     *
     * @param bool $bind Whether to bind the instance to itself.
     */
    public function __construct(bool $bind = true)
    {
        if ($bind) {
            $this->instance(self::class, $this);
        }
    }

    /**
     * Bind an alias to a factory Closure or class name.
     *
     * @param string $alias The class alias.
     * @param Closure|string|null $factory The factory Closure or class name.
     * @param bool $shared Whether the instance of this alias should be shared.
     * @param bool $scoped Whether the instance of this alias is scoped.
     * @return static The Container.
     */
    public function bind(string $alias, Closure|string|null $factory = null, bool $shared = false, bool $scoped = false): static
    {
        $this->unset($alias);
        $this->unscoped($alias);

        $factory ??= $alias;

        $this->bindings[$alias] = [$factory, $shared];

        if ($scoped) {
            $this->scoped[$alias] = true;
        }

        return $this;
    }

    /**
     * Bind a contextual attribute to a handler.
     *
     * @param string $attribute The attribute.
     * @param Closure $handler The hadnler.
     * @return static The Container.
     */
    public function bindAttribute(string $attribute, Closure $handler): static
    {
        $this->contextualAttributes[$attribute] = $handler;

        return $this;
    }

    /**
     * Build a class name, injecting dependencies as required.
     *
     * @param string $className The class name.
     * @param array $arguments The constructor arguments.
     * @param array &$dependencies The dependencies.
     * @return object The class instance.
     *
     * @throws ContainerException if the class is invalid or not instantiable.
     */
    public function build(string $className, array $arguments = [], array &$dependencies = []): object
    {
        if (!class_exists($className)) {
            throw ContainerException::forInvalidClass($className);
        }

        $reflection = new ReflectionClass($className);

        if (!$reflection->isInstantiable()) {
            throw ContainerException::forNotInstantiableClass($className);
        }

        $constructor = $reflection->getConstructor();

        if (!$constructor) {
            return new $className();
        }

        $parameters = $constructor->getParameters();

        $dependencies = $this->resolveDependencies($parameters, $arguments);

        return $reflection->newInstanceArgs($dependencies);
    }

    /**
     * Execute a callable using resolved dependencies.
     *
     * @param array|object|string $callable The callable.
     * @param array $arguments The function arguments.
     * @return mixed The return value of the callable.
     *
     * @throws ContainerException
     */
    public function call(array|object|string $callable, array $arguments = []): mixed
    {
        if (is_string($callable) && str_contains($callable, '::')) {
            $callable = explode('::', $callable, 2);
        }

        if (is_array($callable)) {
            $reflection = new ReflectionMethod($callable[0], $callable[1] ?? '__invoke');

            if ($reflection->isStatic()) {
                $callable[0] = null;
            } else if (is_string($callable[0])) {
                $callable[0] = $this->use($callable[0]);
            }

            $dependencies = $this->resolveDependencies($reflection->getParameters(), $arguments);

            return $reflection->invokeArgs($callable[0], $dependencies);
        }

        if (is_string($callable) && class_exists($callable) && method_exists($callable, '__invoke')) {
            $callable = $this->use($callable);
        }

        $reflection = new ReflectionFunction($callable(...));

        $dependencies = $this->resolveDependencies($reflection->getParameters(), $arguments);

        return $reflection->invokeArgs($dependencies);
    }

    /**
     * Clear the scoped instances, including any dependents.
     *
     * @return static The Container.
     */
    public function clearScoped(): static
    {
        foreach ($this->scoped as $alias => $v) {
            $this->unset($alias, true);
        }

        return $this;
    }

    /**
     * Bind an alias to a class instance.
     *
     * @param string $alias The class alias.
     * @param object $instance The class instance.
     * @return object The instance.
     */
    public function instance(string $alias, object $instance): object
    {
        $this->unset($alias);
        $this->unscoped($alias);

        unset($this->bindings[$alias]);

        $this->instances[$alias] = $instance;

        return $instance;
    }

    /**
     * Bind an alias to a factory Closure or class name as a reusable scoped instance.
     *
     * @param string $alias The class alias.
     * @param Closure|string|null $factory The factory Closure or class name.
     * @return static The Container.
     */
    public function scoped(string $alias, Closure|string|null $factory = null): static
    {
        return $this->bind($alias, $factory, true, true);
    }

    /**
     * Bind an alias to a factory Closure or class name as a reusable instance.
     *
     * @param string $alias The class alias.
     * @param Closure|string|null $factory The factory Closure or class name.
     * @return static The Container.
     */
    public function singleton(string $alias, Closure|string|null $factory = null): static
    {
        return $this->bind($alias, $factory, true);
    }

    /**
     * Remove an alias from the scoped instances.
     *
     * @param string $alias The class alias.
     * @return static The Container.
     */
    public function unscoped(string $alias): static
    {
        unset($this->scoped[$alias]);

        return $this;
    }

    /**
     * Remove an instance and optionally any dependents.
     *
     * @param string $alias The class alias.
     * @param bool $unsetDependents Whether to unset dependents.
     * @return static The Container.
     */
    public function unset(string $alias, bool $unsetDependents = false): static
    {
        $dependents = $unsetDependents ?
            ($this->dependencyMap[$alias] ?? []) :
            [];

        unset($this->dependencyMap[$alias]);
        unset($this->instances[$alias]);

        foreach ($dependents as $dependent => $v) {
            $this->unset($dependent, true);
        }

        return $this;
    }

    /**
     * Use an instance of a class.
     *
     * @param string $alias The class alias.
     * @param array $arguments The constructor arguments.
     * @return object The class instance.
     */
    public function use(string $alias, array $arguments = []): object
    {
        if (array_key_exists($alias, $this->instances) && $arguments === []) {
            return $this->instances[$alias];
        }

        if (!array_key_exists($alias, $this->bindings)) {
            return $this->build($alias, $arguments);
        }

        [$factory, $shared] = $this->bindings[$alias];

        $dependencies = [];

        if (is_string($factory)) {
            $className = $factory;
            $instance = $this->build($className, $arguments, $dependencies);
        } else {
            $instance = $this->call($factory, $arguments);
        }

        if (!$shared || $arguments !== []) {
            return $instance;
        }

        foreach ($dependencies as $dependency) {
            $key = array_search($dependency, $this->instances);

            if ($key !== false) {
                $this->dependencyMap[$key] ??= [];
                $this->dependencyMap[$key][$alias] = true;
            }
        }

        return $this->instances[$alias] = $instance;
    }

    /**
     * Resolve dependencies from parameters.
     *
     * @param array $parameters The function parameters.
     * @param array $arguments The provided arguments.
     * @return array The dependencies.
     */
    protected function resolveDependencies(array $parameters, array $arguments): array
    {
        $dependencies = [];
        foreach ($parameters as $parameter) {
            $paramName = $parameter->getName();

            if (array_key_exists($paramName, $arguments)) {
                $dependencies[] = $arguments[$paramName];
                unset($arguments[$paramName]);

                continue;
            }

            $attribute = $parameter->getAttributes(ContextualAttribute::class, ReflectionAttribute::IS_INSTANCEOF)[0] ?? null;

            if ($attribute) {
                $instance = $attribute->newInstance();
                $name = $attribute->getName();

                if (array_key_exists($name, $this->contextualAttributes)) {
                    $dependencies[] = $this->call($this->contextualAttributes[$name], ['attribute' => $instance]);
                } else {
                    $dependencies[] = $instance->resolve($this);
                }

                continue;
            }

            try {
                $paramType = $parameter->getType();

                if ($paramType instanceof ReflectionNamedType && !$paramType->isBuiltIn()) {
                    $typeName = $paramType->getName();

                    $definingClass = $parameter->getDeclaringClass()->getName() ?? null;

                    $className = match ($typeName) {
                        'parent' => $parameter->getDeclaringClass()->getParentClass()->getName(),
                        'self' => $parameter->getDeclaringClass()->getName(),
                        default => $typeName
                    };

                    if ($className === $definingClass) {
                        if (!$parameter->allowsNull()) {
                            throw ContainerException::forSelfDependency($className);
                        }

                        $dependency = null;
                    } else {
                        $dependency = $this->use($className);
                    }

                    $dependencies[] = $dependency;

                    continue;
                }
            } catch (Throwable $e) {
            }

            if ($parameter->isDefaultValueAvailable()) {
                $dependencies[] = $parameter->getDefaultValue();
            } else if ($parameter->allowsNull()) {
                $dependencies[] = null;
            } else if (!$parameter->isVariadic()) {
                throw ContainerException::forUnresolvedDependency($paramName);
            }
        }

        $arguments = array_values($arguments);

        return array_merge($dependencies, $arguments);
    }
}
