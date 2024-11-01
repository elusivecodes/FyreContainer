<?php
declare(strict_types=1);

namespace Tests\Mock;

class ItemService
{
    protected Item $item;

    public function __construct(
        #[ItemContext('test')] Item $item
    ) {
        $this->item = $item;
    }

    public function getItem(): Item
    {
        return $this->item;
    }
}
