<?php

namespace Kris\LaravelFormBuilder\Events;

use Kris\LaravelFormBuilder\Fields\FormField;
use Kris\LaravelFormBuilder\Form;

class AfterFieldCreation
{
    protected Form $form;

    protected FormField $field;

    public function __construct(Form $form, FormField $field)
    {
        $this->form = $form;
        $this->field = $field;
    }

    public function getForm(): Form
    {
        return $this->form;
    }

    public function getField(): FormField
    {
        return $this->field;
    }
}
