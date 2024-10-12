<?php

namespace Kris\LaravelFormBuilder;

use Illuminate\Support\Facades\App;

trait FormBuilderTrait
{
    protected function form(string $name, array $options = [], array $data = []): Form
    {
        return App::make('laravel-form-builder')->create($name, $options, $data);
    }

    protected function plain(array $options = [], array $data = []): Form
    {
        return App::make('laravel-form-builder')->plain($options, $data);
    }
}
