<?php
declare(strict_types=1);

namespace Tests\Mock;

use Attribute;
use Fyre\Container\Container;
use Fyre\Container\ContextualAttribute;

#[Attribute(Attribute::TARGET_PARAMETER)]
class ItemContext extends ContextualAttribute
{
    public function __construct(
        protected string $value
    ) {}

    public function resolve(Container $container): Item
    {
        return $container->build(Item::class, ['value' => $this->value]);
    }
}
