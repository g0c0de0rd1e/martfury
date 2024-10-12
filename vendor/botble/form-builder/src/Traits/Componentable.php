<?php

namespace Kris\LaravelFormBuilder\Traits;

use BadMethodCallException;
use Illuminate\Support\Arr;
use Illuminate\Support\HtmlString;

trait Componentable
{
    protected static array $components = [];

    public static function component($name, $view, array $signature): void
    {
        static::$components[$name] = compact('view', 'signature');
    }

    public function __call($method, $parameters)
    {
        if (static::hasComponent($method)) {
            return $this->renderComponent($method, $parameters);
        }

        throw new BadMethodCallException("Method {$method} does not exist.");
    }

    public static function hasComponent(string $name): bool
    {
        return isset(static::$components[$name]);
    }

    protected function renderComponent(string $name, array $arguments): HtmlString
    {
        $component = static::$components[$name];
        $data = $this->getComponentData($component['signature'], $arguments);

        return new HtmlString(
            $this->view->make($component['view'], $data)->render()
        );
    }

    protected function getComponentData(array $signature, array $arguments): array
    {
        $data = [];

        $i = 0;
        foreach ($signature as $variable => $default) {
            // If the "variable" value is actually a numeric key, we can assume that
            // no default had been specified for the component argument and we'll
            // just use null instead, so that we can treat them all the same.
            if (is_numeric($variable)) {
                $variable = $default;
                $default = null;
            }

            $data[$variable] = Arr::get($arguments, $i, $default);

            $i++;
        }

        return $data;
    }
}
