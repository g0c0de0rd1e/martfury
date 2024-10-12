<?php

namespace Kris\LaravelFormBuilder\Events;

use Kris\LaravelFormBuilder\Fields\FormField;
use Kris\LaravelFormBuilder\Rules;

class AfterCollectingFieldRules
{
    protected FormField $field;

    protected Rules $rules;

    public function __construct(FormField $field, Rules $rules)
    {
        $this->field = $field;
        $this->rules = $rules;
    }

    public function getField(): FormField
    {
        return $this->field;
    }

    public function getRules(): Rules
    {
        return $this->rules;
    }
}
