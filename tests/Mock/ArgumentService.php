<?php
declare(strict_types=1);

namespace Tests\Mock;

class ArgumentService
{
    protected array $arguments;

    public function __construct(int $a = 1, int $b = 2, int $c = 3)
    {
        $this->arguments = [$a, $b, $c];
    }

    public function getArguments(): array
    {
        return $this->arguments;
    }
}
