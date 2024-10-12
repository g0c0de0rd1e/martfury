<?php

namespace Kris\LaravelFormBuilder\Fields;

class ButtonType extends FormField
{
    public function getAllAttributes(): array
    {
        // Don't collect input for buttons.
        return [];
    }

    protected function getTemplate(): string
    {
        return 'button';
    }

    protected function getDefaults(): array
    {
        return [
            'wrapper' => false,
            'attr' => ['type' => $this->type],
        ];
    }
}
