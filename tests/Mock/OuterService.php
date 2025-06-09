<?php
declare(strict_types=1);

namespace Tests\Mock;

class OuterService
{
    public function __construct(
        protected InnerService $innerService
    ) {}

    public function getInnerService(): InnerService
    {
        return $this->innerService;
    }
}
