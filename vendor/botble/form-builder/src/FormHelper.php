<?php

namespace Kris\LaravelFormBuilder;

use Illuminate\Contracts\Support\MessageBag;
use Illuminate\Contracts\View\Factory as View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Translation\Translator;
use InvalidArgumentException;
use Kris\LaravelFormBuilder\Events\AfterCollectingFieldRules;
use Kris\LaravelFormBuilder\Fields\CheckableType;
use Kris\LaravelFormBuilder\Fields\FormField;

class FormHelper
{
    protected static array $reservedFieldNames = [
        'save',
    ];
    protected static array $availableFieldTypes = [
        'text' => 'InputType',
        'email' => 'InputType',
        'url' => 'InputType',
        'tel' => 'InputType',
        'search' => 'InputType',
        'password' => 'InputType',
        'hidden' => 'InputType',
        'number' => 'InputType',
        'date' => 'InputType',
        'file' => 'InputType',
        'image' => 'InputType',
        'color' => 'InputType',
        'datetime-local' => 'InputType',
        'month' => 'InputType',
        'range' => 'InputType',
        'time' => 'InputType',
        'week' => 'InputType',
        'select' => 'SelectType',
        'textarea' => 'TextareaType',
        'button' => 'ButtonType',
        'buttongroup' => 'ButtonGroupType',
        'submit' => 'ButtonType',
        'reset' => 'ButtonType',
        'radio' => 'CheckableType',
        'checkbox' => 'CheckableType',
        'choice' => 'ChoiceType',
        'form' => 'ChildFormType',
        'entity' => 'EntityType',
        'collection' => 'CollectionType',
        'repeated' => 'RepeatedType',
        'static' => 'StaticType',
    ];
    protected View $view;
    protected Translator $translator;
    protected array $config;
    private array $customTypes = [];

    public function __construct(View $view, Translator $translator, array $config = [])
    {
        $this->view = $view;
        $this->translator = $translator;
        $this->config = $config;
        $this->loadCustomTypes();
    }

    private function loadCustomTypes(): void
    {
        $customFields = (array) $this->getConfig('custom_fields');

        if (! empty($customFields)) {
            foreach ($customFields as $fieldName => $fieldClass) {
                $this->addCustomField($fieldName, $fieldClass);
            }
        }
    }

    public function getConfig(string $key = null, string $default = null, array $customConfig = []): mixed
    {
        $config = array_replace_recursive($this->config, $customConfig);

        if ($key) {
            return Arr::get($config, $key, $default);
        }

        return $config;
    }

    public function addCustomField(string $name, string $class): string
    {
        if (! $this->hasCustomField($name)) {
            return $this->customTypes[$name] = $class;
        }

        throw new InvalidArgumentException('Custom field [' . $name . '] already exists on this form object.');
    }

    public function hasCustomField(string $name): bool
    {
        return array_key_exists($name, $this->customTypes);
    }

    public function getView(): View
    {
        return $this->view;
    }

    public function mergeOptions(array $targetOptions, array $sourceOptions): array
    {
        return array_replace_recursive($targetOptions, $sourceOptions);
    }

    public function getFieldType($type)
    {
        $types = array_keys(static::$availableFieldTypes);

        if (! $type || trim($type) == '') {
            throw new InvalidArgumentException('Field type must be provided.');
        }

        if ($this->hasCustomField($type)) {
            return $this->customTypes[$type];
        }

        if (in_array($type, $types, true)) {
            $namespace = __NAMESPACE__ . '\\Fields\\';

            return $namespace . static::$availableFieldTypes[$type];
        }

        if (class_exists($type)) {
            if (! is_subclass_of($type, FormField::class)) {
                throw new InvalidArgumentException(
                    sprintf('Could not load type "%s": class is not a subclass of "%s".', $type, FormField::class)
                );
            }

            return $type;
        }

        throw new InvalidArgumentException(
            sprintf(
                'Unsupported field type [%s]. Available types are: %s',
                $type,
                join(', ', array_merge($types, array_keys($this->customTypes)))
            )
        );
    }

    public function prepareAttributes($options): ?string
    {
        if (! $options) {
            return null;
        }

        $attributes = [];

        foreach ($options as $name => $option) {
            if ($option !== null) {
                $name = is_numeric($name) ? $option : $name;
                $attributes[] = $name . '="' . $option . '" ';
            }
        }

        return join('', $attributes);
    }

    public function convertModelToArray($model): object|array|null
    {
        if (! $model) {
            return null;
        }

        if ($model instanceof Model) {
            return $model->toArray();
        }

        if ($model instanceof Collection) {
            return $model->all();
        }

        return $model;
    }

    public function formatLabel(?string $name): ?string
    {
        if (! $name) {
            return null;
        }

        if ($this->translator->has($name)) {
            $translatedName = $this->translator->get($name);

            if (is_string($translatedName)) {
                return $translatedName;
            }
        }

        return ucfirst(str_replace('_', ' ', $name));
    }

    public function createRulesParser(FormField $field): RulesParser
    {
        return new RulesParser($field);
    }

    public function mergeFieldsRules(array $fields): Rules
    {
        $rules = new Rules([]);

        foreach ($fields as $field) {
            $rules->append($this->getFieldValidationRules($field));
        }

        return $rules;
    }

    public function getFieldValidationRules(FormField $field): Rules
    {
        $fieldRules = $field->getValidationRules();

        if (is_array($fieldRules)) {
            $fieldRules = Rules::fromArray($fieldRules)->setFieldName($field->getNameKey());
        }

        $formBuilder = $field->getParent()->getFormBuilder();
        $formBuilder->fireEvent(new AfterCollectingFieldRules($field, $fieldRules));

        return $fieldRules;
    }

    public function mergeAttributes(array $fields): array
    {
        $attributes = [];
        foreach ($fields as $field) {
            $attributes = array_merge($attributes, $field->getAllAttributes());
        }

        return $attributes;
    }

    public function alterFieldValues(Form $form, array &$values): void
    {
        $this->alterFieldValuesBools($form, $values);

        // Alter the form's child forms recursively
        foreach ($form->getFields() as $name => $field) {
            if (method_exists($field, 'alterFieldValues')) {
                $fullName = $this->transformToDotSyntax($name);

                $subValues = (array) Arr::get($values, $fullName);
                $field->alterFieldValues($subValues);
                Arr::set($values, $fullName, $subValues);
            }
        }

        // Alter the form itself
        $form->alterFieldValues($values);
    }

    public function alterFieldValuesBools(Form $form, array &$values): void
    {
        $fields = $this->getBoolableFields($form);

        foreach ($fields as $name) {
            $value = Arr::get($values, $name, -1);
            if ($value !== -1) {
                Arr::set($values, $name, (int) (bool) $value);
            }
        }
    }

    public function getBoolableFields(Form $form): array
    {
        $fields = [];
        foreach ($form->getFields() as $name => $field) {
            if ($field instanceof CheckableType && $field->getOption('value') == CheckableType::DEFAULT_VALUE) {
                $fields[] = $this->transformToDotSyntax($name);
            }
        }

        return $fields;
    }

    public function transformToDotSyntax($string): string
    {
        return str_replace(['.', '[]', '[', ']'], ['_', '', '.', ''], $string ?? '');
    }

    public function alterValid(Form $form, Form $mainForm, bool &$isValid): void
    {
        // Alter the form itself
        $messages = $form->alterValid($mainForm, $isValid);

        // Add messages to the existing Bag
        if ($messages) {
            $messageBag = $mainForm->getValidator()->getMessageBag();
            $this->appendMessagesWithPrefix($messageBag, $form->getName(), $messages);
        }

        // Alter the form's child forms recursively
        foreach ($form->getFields() as $name => $field) {
            if (method_exists($field, 'alterValid')) {
                $field->alterValid($mainForm, $isValid);
            }
        }
    }

    public function appendMessagesWithPrefix(MessageBag $messageBag, ?string $prefix, array $keyedMessages): void
    {
        foreach ($keyedMessages as $key => $messages) {
            if ($prefix) {
                $key = $this->transformToDotSyntax($prefix . '[' . $key . ']');
            }

            foreach ((array) $messages as $message) {
                $messageBag->add($key, $message);
            }
        }
    }

    public function transformToBracketSyntax(?string $string): string
    {
        $name = explode('.', $string);
        if ($name && count($name) == 1) {
            return $name[0];
        }

        $first = array_shift($name);

        return $first . '[' . implode('][', $name) . ']';
    }

    public function getTranslator(): Translator
    {
        return $this->translator;
    }

    public function checkFieldName(string $name, string $className): bool
    {
        if (! $name || trim($name) == '') {
            throw new InvalidArgumentException(
                "Please provide valid field name for class [{$className}]"
            );
        }

        if (in_array($name, static::$reservedFieldNames)) {
            throw new InvalidArgumentException(
                "Field name [{$name}] in form [{$className}] is a reserved word. Please use a different field name." .
                "\nList of all reserved words: " . join(', ', static::$reservedFieldNames)
            );
        }

        return true;
    }
}
