<?php

namespace Kris\LaravelFormBuilder\Events;

use Kris\LaravelFormBuilder\Form;

class AfterFormCreation
{
    protected Form $form;

    public function __construct(Form $form)
    {
        $this->form = $form;
    }

    public function getForm(): Form
    {
        return $this->form;
    }
}
