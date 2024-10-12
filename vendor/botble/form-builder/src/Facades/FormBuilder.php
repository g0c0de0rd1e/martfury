<?php

namespace Kris\LaravelFormBuilder\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Kris\LaravelFormBuilder\FormBuilder
 */
class FormBuilder extends Facade
{
    public static function getFacadeAccessor(): string
    {
        return 'laravel-form-builder';
    }
}
