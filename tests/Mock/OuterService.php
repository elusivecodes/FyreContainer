<?php
declare(strict_types=1);

namespace Tests\Mock;

class OuterService
{
    protected InnerService $innerService;

    public function __construct(InnerService $innerService)
    {
        $this->innerService = $innerService;
    }

    public function getInnerService(): InnerService
    {
        return $this->innerService;
    }
}
