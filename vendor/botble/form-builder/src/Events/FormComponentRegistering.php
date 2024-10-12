<?php

namespace Kris\LaravelFormBuilder\Events;

use Illuminate\Foundation\Events\Dispatchable;

class FormComponentRegistering
{
    use Dispatchable;

    public function __construct(public object $form)
    {
    }
}
