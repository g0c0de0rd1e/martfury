<?php

namespace Kris\LaravelFormBuilder\Filters\Collection;

use Kris\LaravelFormBuilder\Filters\FilterInterface;

class BaseName implements FilterInterface
{
    public function filter($value, $options = []): string
    {
        $value = (string) $value;

        return basename($value);
    }

    public function getName(): string
    {
        return 'BaseName';
    }
}
