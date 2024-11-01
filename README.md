# FyreContainer

**FyreContainer** is a free, open-source container library for *PHP*.


## Table Of Contents
- [Installation](#installation)
- [Basic Usage](#basic-usage)
- [Methods](#methods)



## Installation

**Using Composer**

```
composer require fyre/container
```

In PHP:

```php
use Fyre\Container\Container;
```


## Basic Usage

- `$bind` is a boolean indicating whether to bind the container instance to itself, and will default to *true*.

```php
$container = new Container($bind);
```


## Methods

**Bind**

Bind an alias to a factory *Closure* or class name.

- `$alias` is a string representing the alias.
- `$factory` is a string representing the class name, or a *Closure* that returns an instance of the class, and will default to the `$alias`.

```php
$container->bind($alias, $factory);
```

**Bind Attribute**

Bind a contextual attribute to a handler.

- `$attribute` is a string representing the attribute.
- `$handler` is a *Closure* that will resolve a value from the attribute.

```php
$container->bindAttribute($attribute, $handler);
```

**Build**

Build a class name, injecting dependencies as required.

- `$className` is a string representing the class name.
- `$arguments` is an array containing the named arguments for the class constructor.

```php
$container->build($className, $arguments);
```

**Call**

Execute a callable using resolved dependencies.

- `$callable` is an array, string or object representing the callable.
- `$arguments` is an array containing the named arguments for the callabck.

```php
$container->build($callable, $arguments);
```

**Clear Scoped**

Clear the scoped instances.

```php
$container->clearScoped();
```

**Instance**

Bind an alias to a class instance.

- `$alias` is a string representing the alias.
- `$instance` is an object representing the class instance.

```php
$container->instance($alias, $instance);
```

**Scoped**

Bind an alias to a factory *Closure* or class name as a reusable scoped instance.

- `$alias` is a string representing the alias.
- `$factory` is a string representing the class name, or a *Closure* that returns an instance of the class, and will default to the `$alias`.

```php
$container->scoped($alias, $factory);
```

**Singleton**

Bind an alias to a factory *Closure* or class name as a reusable instance.

- `$alias` is a string representing the alias.
- `$factory` is a string representing the class name, or a *Closure* that returns an instance of the class, and will default to the `$alias`.

```php
$container->singleton($alias, $factory);
```

**Use**

Use an instance of a class.

- `$alias` is a string representing the alias.
- `$arguments` is an array containing the named arguments for the class constructor.

```php
$container->use($alias, $arguments);
```