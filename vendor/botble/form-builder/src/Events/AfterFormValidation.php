<?php

namespace Kris\LaravelFormBuilder\Events;

use Illuminate\Contracts\Validation\Validator;
use Kris\LaravelFormBuilder\Form;

class AfterFormValidation
{
    protected Form $form;

    protected Validator $validator;

    protected bool $valid;

    public function __construct(Form $form, Validator $validator, bool $valid)
    {
        $this->form = $form;
        $this->validator = $validator;
        $this->valid = $valid;
    }

    public function getForm(): Form
    {
        return $this->form;
    }

    public function getValidator(): Validator
    {
        return $this->validator;
    }

    public function isValid(): bool
    {
        return $this->valid;
    }
}
