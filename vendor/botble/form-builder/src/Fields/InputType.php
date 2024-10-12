<?php

namespace Kris\LaravelFormBuilder\Fields;

class InputType extends FormField
{
    protected function getTemplate(): string
    {
        return 'text';
    }
}
