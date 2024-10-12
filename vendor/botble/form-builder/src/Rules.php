<?php

namespace Kris\LaravelFormBuilder;

use InvalidArgumentException;

class Rules
{
    protected ?string $fieldName;

    protected array $rules;

    protected array $attributes;

    protected array $messages;

    public function __construct(array $rules, array $attributes = [], array $messages = [])
    {
        $this->rules = $rules;
        $this->attributes = $attributes;
        $this->messages = $messages;
    }

    public function setFieldName(string $name): static
    {
        $this->fieldName = $name;

        return $this;
    }

    public function addFieldRule($rule, string $fieldName = null): void
    {
        $rules = $this->getFieldRules($fieldName);
        $rules[] = $rule;
        $this->setFieldRules($rules, $fieldName);
    }

    public function getFieldRules($fieldName = null)
    {
        $fieldName = $this->ensureFieldName($fieldName);

        $rules = $this->rules;

        return $rules[$fieldName] ?? [];
    }

    protected function ensureFieldName(string $fieldName): ?string
    {
        if (! $fieldName) {
            if (! $this->fieldName) {
                throw new InvalidArgumentException('Field functions on non-field Rules need explicit field name');
            }

            $fieldName = $this->fieldName;
        }

        return $fieldName;
    }

    public function setFieldRules(array $rules, string $fieldName = null): void
    {
        $fieldName = $this->ensureFieldName($fieldName);
        $this->rules[$fieldName] = $rules;
    }

    public function append(Rules|array $rules): static
    {
        if (is_array($rules)) {
            $rules = static::fromArray($rules);
        }

        $this->rules = array_replace_recursive($this->rules, $rules->getRules());
        $this->attributes = array_replace_recursive($this->attributes, $rules->getAttributes());
        $this->messages = array_replace_recursive($this->messages, $rules->getMessages());

        return $this;
    }

    public static function fromArray(array $rules): static
    {
        if (! $rules) {
            return new static([]);
        }

        $rules += [
            'rules' => [],
            'attributes' => [],
            'error_messages' => [],
        ];

        return new static($rules['rules'], $rules['attributes'], $rules['error_messages']);
    }

    public function getRules(): array
    {
        return $this->rules;
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function getMessages(): array
    {
        return $this->messages;
    }
}
