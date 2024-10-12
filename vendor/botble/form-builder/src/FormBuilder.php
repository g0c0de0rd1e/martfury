<?php

namespace Kris\LaravelFormBuilder;

use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;
use InvalidArgumentException;
use Kris\LaravelFormBuilder\Events\AfterFormCreation;

class FormBuilder
{
    protected Container $container;

    protected FormHelper $formHelper;

    protected EventDispatcher $eventDispatcher;

    protected string $plainFormClass = Form::class;

    public function __construct(Container $container, FormHelper $formHelper, EventDispatcher $eventDispatcher)
    {
        $this->container = $container;
        $this->formHelper = $formHelper;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function fireEvent($event): ?array
    {
        return $this->eventDispatcher->dispatch($event);
    }

    public function create(string $formClass, array $options = [], array $data = []): Form
    {
        $class = $this->getNamespaceFromConfig() . $formClass;

        if (! class_exists($class)) {
            throw new InvalidArgumentException(
                'Form class with name ' . $class . ' does not exist.'
            );
        }

        $form = $this->setDependenciesAndOptions($this->container->make($class), $options, $data);

        $form->buildForm();

        $this->eventDispatcher->dispatch(new AfterFormCreation($form));

        $form->filterFields();

        return $form;
    }

    protected function getNamespaceFromConfig(): string
    {
        $namespace = $this->formHelper->getConfig('default_namespace');

        if (! $namespace) {
            return '';
        }

        return $namespace . '\\';
    }

    public function setDependenciesAndOptions(Form $instance, array $options = [], array $data = []): Form
    {
        return $instance
            ->addData($data)
            ->setRequest($this->container->make('request'))
            ->setFormHelper($this->formHelper)
            ->setEventDispatcher($this->eventDispatcher)
            ->setFormBuilder($this)
            ->setValidator($this->container->make('validator'))
            ->setFormOptions($options);
    }

    public function createByArray($items, array $options = [], array $data = []): mixed
    {
        $form = $this->setDependenciesAndOptions(
            $this->container->make($this->plainFormClass),
            $options,
            $data
        );

        $this->buildFormByArray($form, $items);

        $this->eventDispatcher->dispatch(new AfterFormCreation($form));

        $form->filterFields();

        return $form;
    }

    public function buildFormByArray($form, $items): void
    {
        foreach ($items as $item) {
            if (! isset($item['name'])) {
                throw new InvalidArgumentException(
                    'Name is not set in form array.'
                );
            }
            $name = $item['name'];
            $type = isset($item['type']) && $item['type'] ? $item['type'] : '';
            $modify = isset($item['modify']) && $item['modify'] ? $item['modify'] : false;
            unset($item['name']);
            unset($item['type']);
            unset($item['modify']);
            $form->add($name, $type, $item, $modify);
        }
    }

    public function getFormClass(): string
    {
        return $this->plainFormClass;
    }

    public function setFormClass(string $class): void
    {
        $parent = Form::class;
        if (! is_a($class, $parent, true)) {
            throw new InvalidArgumentException("Class must be or extend $parent; $class is not.");
        }

        $this->plainFormClass = $class;
    }

    public function plain(array $options = [], array $data = []): Form
    {
        $form = $this->setDependenciesAndOptions(
            $this->container->make($this->plainFormClass),
            $options,
            $data
        );

        $this->eventDispatcher->dispatch(new AfterFormCreation($form));

        $form->filterFields();

        return $form;
    }
}
