<?php

namespace Kris\LaravelFormBuilder\Events;

use Illuminate\Contracts\Validation\Validator;
use Kris\LaravelFormBuilder\Form;

class BeforeFormValidation
{
    protected Form $form;

    protected Validator $validator;

    public function __construct(Form $form, Validator $validator)
    {
        $this->form = $form;
        $this->validator = $validator;
    }

    public function getForm(): Form
    {
        return $this->form;
    }

    public function getValidator(): Validator
    {
        return $this->validator;
    }
}
