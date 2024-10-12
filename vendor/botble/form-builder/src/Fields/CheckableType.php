<?php

namespace Kris\LaravelFormBuilder\Fields;

class CheckableType extends FormField
{
    public const DEFAULT_VALUE = 1;

    /**
     * @inheritdoc
     */
    protected $valueProperty = 'checked';

    /**
     * @inheritdoc
     */
    public function getDefaults(): array
    {
        return [
            'attr' => ['class' => null, 'id' => $this->getName()],
            'value' => self::DEFAULT_VALUE,
            'checked' => null,
        ];
    }

    protected function getTemplate()
    {
        return $this->type;
    }

    /**
     * {@inheritdoc}
     */
    protected function isValidValue($value)
    {
        return $value !== null;
    }
}
