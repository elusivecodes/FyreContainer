<?php
declare(strict_types=1);

namespace Tests\Mock;

class Item
{
    public function __construct(
        protected string|null $value = null
    ) {}

    public function getValue(): string|null
    {
        return $this->value;
    }
}
