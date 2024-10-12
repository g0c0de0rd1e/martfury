<?php

namespace Kris\LaravelFormBuilder\Events;

use Illuminate\Foundation\Events\Dispatchable;

class FormComponentRegistered
{
    use Dispatchable;

    public function __construct(public object $form)
    {
    }
}
