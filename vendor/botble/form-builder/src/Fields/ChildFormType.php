<?php

namespace Kris\LaravelFormBuilder\Fields;

use BadMethodCallException;
use InvalidArgumentException;
use Kris\LaravelFormBuilder\Form;

class ChildFormType extends ParentType
{
    protected ?Form $form;

    public function getForm(): Form
    {
        return $this->form;
    }

    public function getAllAttributes(): array
    {
        // Collect all children's attributes.
        return $this->parent->getFormHelper()->mergeAttributes($this->children);
    }

    public function alterFieldValues(array &$values): void
    {
        $this->parent->getFormHelper()->alterFieldValues($this->form, $values);
    }

    public function alterValid(Form $mainForm, &$isValid): void
    {
        $this->parent->getFormHelper()->alterValid($this->form, $mainForm, $isValid);
    }

    public function removeChild($key): ParentType|ChildFormType|static
    {
        if ($this->getChild($key)) {
            $this->form->remove($key);

            return parent::removeChild($key);
        }

        return $this;
    }

    public function __call($method, $arguments)
    {
        if (method_exists($this->form, $method)) {
            return call_user_func_array([$this->form, $method], $arguments);
        }

        throw new BadMethodCallException(
            'Method [' . $method . '] does not exist on form [' . get_class($this->form) . ']'
        );
    }

    protected function getTemplate(): string
    {
        return 'child_form';
    }

    protected function getDefaults(): array
    {
        return [
            'class' => null,
            'value' => null,
            'formOptions' => [],
            'data' => [],
            'exclude' => [],
        ];
    }

    protected function createChildren(): void
    {
        $this->form = $this->getClassFromOptions();

        if ($this->form->getFormOption('files')) {
            $this->parent->setFormOption('files', true);
        }
        $model = $this->getOption($this->valueProperty);
        if ($this->isValidValue($model)) {
            foreach ($this->form->getFields() as $name => $field) {
                $field->setValue($this->getModelValueAttribute($model, $name));
            }
        }

        $this->children = $this->form->getFields();
    }

    protected function getClassFromOptions(): Form
    {
        if ($this->form instanceof Form) {
            return $this->form->setName($this->name);
        }

        $class = $this->getOption('class');

        if (! $class) {
            throw new InvalidArgumentException(
                'Please provide full name or instance of Form class.'
            );
        }

        if (is_string($class)) {
            $options = [
                'model' => $this->getOption($this->valueProperty) ?: $this->parent->getModel(),
                'name' => $this->name,
                'language_name' => $this->parent->getLanguageName(),
                'translation_template' => $this->parent->getTranslationTemplate(),
            ];

            if (! $this->parent->clientValidationEnabled()) {
                $options['client_validation'] = false;
            }

            if (! $this->parent->haveErrorsEnabled()) {
                $options['errors_enabled'] = false;
            }

            $formOptions = array_merge($options, $this->getOption('formOptions'));

            $data = array_merge($this->parent->getData(), $this->getOption('data'));

            return $this->parent->getFormBuilder()->create($class, $formOptions, $data);
        }

        if ($class instanceof Form) {
            $class->setName($this->name, false);
            $class->setModel($class->getModel() ?: $this->parent->getModel());

            if (! $class->getData()) {
                $class->addData($this->parent->getData());
            }

            if (! $class->getLanguageName()) {
                $class->setLanguageName($this->parent->getLanguageName());
            }

            if (! $class->getTranslationTemplate()) {
                $class->setTranslationTemplate($this->parent->getTranslationTemplate());
            }

            if (! $this->parent->clientValidationEnabled()) {
                $class->setClientValidationEnabled(false);
            }

            if (! $this->parent->haveErrorsEnabled()) {
                $class->setErrorsEnabled(false);
            }

            return $class->setName($this->name);
        }

        throw new InvalidArgumentException(
            'Class provided does not exist or it passed in wrong format.'
        );
    }

    protected function isValidValue($value): bool
    {
        return $value !== null;
    }

    protected function getRenderData(): array
    {
        $data = parent::getRenderData();
        $data['child_form'] = $this->form;

        return $data;
    }
}
