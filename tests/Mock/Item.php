<?php
declare(strict_types=1);

namespace Tests\Mock;

class Item
{
    protected string|null $value;

    public function __construct(string|null $value = null)
    {
        $this->value = $value;
    }

    public function getValue(): string|null
    {
        return $this->value;
    }
}
