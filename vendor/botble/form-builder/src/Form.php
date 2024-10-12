<?php

namespace Kris\LaravelFormBuilder;

use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Validation\Factory as ValidatorFactory;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use InvalidArgumentException;
use Kris\LaravelFormBuilder\Events\AfterFieldCreation;
use Kris\LaravelFormBuilder\Events\AfterFormValidation;
use Kris\LaravelFormBuilder\Events\BeforeFormValidation;
use Kris\LaravelFormBuilder\Fields\FormField;
use Kris\LaravelFormBuilder\Filters\FilterResolver;

class Form
{
    protected array $fields = [];

    protected array|Model $model = [];

    protected EventDispatcher $eventDispatcher;

    protected FormHelper $formHelper;

    protected array $formOptions = [
        'method' => 'GET',
        'url' => null,
        'attr' => [],
    ];

    protected array $formConfig = [];

    protected array $data = [];

    protected bool $showFieldErrors = true;

    protected bool $clientValidationEnabled = true;

    protected ?string $name = null;

    protected ?FormBuilder $formBuilder;

    protected ValidatorFactory $validatorFactory;

    protected ?Validator $validator = null;

    protected Request $request;

    protected array $exclude = [];

    protected bool $rebuilding = false;

    protected ?string $templatePrefix = null;

    protected string $languageName = '';

    protected string $translationTemplate = '';

    protected bool $lockFiltering = false;

    protected string $errorBag = 'default';

    public function addBefore($name, $fieldName, $type = 'text', $options = [], $modify = false): static
    {
        $offset = array_search($name, array_keys($this->fields));

        $beforeFields = array_slice($this->fields, 0, $offset);
        $afterFields = array_slice($this->fields, $offset);

        $this->fields = $beforeFields;

        $this->add($fieldName, $type, $options, $modify);

        $this->fields += $afterFields;

        return $this;
    }

    public function add(string $name, string $type = 'text', array|Arrayable $options = [], bool $modify = false): static
    {
        $this->formHelper->checkFieldName($name, get_class($this));

        if ($this->rebuilding && ! $this->has($name)) {
            return $this;
        }

        $this->addField($this->makeField($name, $type, $options), $modify);

        return $this;
    }

    public function has(string $name): bool
    {
        return array_key_exists($name, $this->fields);
    }

    protected function addField(FormField $field, bool $modify = false): static
    {
        if (! $modify && ! $this->rebuilding) {
            $this->preventDuplicate($field->getRealName());
        }

        if ($field->getType() == 'file') {
            $this->formOptions['files'] = true;
        }

        $this->fields[$field->getRealName()] = $field;

        return $this;
    }

    protected function preventDuplicate(string $name): void
    {
        if ($this->has($name)) {
            throw new InvalidArgumentException('Field [' . $name . '] already exists in the form ' . get_class($this));
        }
    }

    protected function makeField(string $name, string $type = 'text', array|Arrayable $options = []): FormField
    {
        if ($options instanceof Arrayable) {
            $options = $options->toArray();
        }

        $this->setupFieldOptions($name, $options);

        $fieldName = $this->getFieldName($name);

        $fieldType = $this->getFieldType($type);

        $field = new $fieldType($fieldName, $type, $this, $options);

        $this->eventDispatcher->dispatch(new AfterFieldCreation($this, $field));

        return $field;
    }

    protected function setupFieldOptions(string $name, array|Arrayable &$options): void
    {
        $options['real_name'] = $name;
    }

    protected function getFieldName(string $name): string
    {
        $formName = $this->getName();
        if ($formName !== null) {
            if (str_contains($formName, '[') || str_contains($formName, ']')) {
                return $this->formHelper->transformToBracketSyntax(
                    $this->formHelper->transformToDotSyntax(
                        $formName . '[' . $name . ']'
                    )
                );
            }

            return $formName . '[' . $name . ']';
        }

        return $name;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name, bool $rebuild = true): static
    {
        $this->name = $name;

        if ($rebuild) {
            $this->rebuildForm();
        }

        return $this;
    }

    protected function getFieldType(string $type): string
    {
        return $this->formHelper->getFieldType($type);
    }

    public function rebuildForm(): static
    {
        $this->rebuilding = true;
        // If form is plain, buildForm method is empty, so we need to take
        // existing fields and add them again
        if ($this->isPlain()) {
            foreach ($this->fields as $name => $field) {
                // Remove any temp variables added in previous instance
                $options = Arr::except($field->getOptions(), 'tmp');
                $this->add($name, $field->getType(), $options);
            }
        } else {
            $this->buildForm();
        }
        $this->rebuilding = false;

        return $this;
    }

    protected function isPlain(): bool
    {
        if ($this->formBuilder === null) {
            throw new \RuntimeException('FormBuilder is not set');
        }

        return static::class === $this->formBuilder->getFormClass();
    }

    public function buildForm(): void
    {
    }

    public function addAfter(string $name, string $fieldName, string $type = 'text', array|Arrayable $options = [], bool $modify = false): static
    {
        if ($options instanceof Arrayable) {
            $options = $options->toArray();
        }

        $offset = array_search($name, array_keys($this->fields));

        $beforeFields = array_slice($this->fields, 0, $offset + 1);
        $afterFields = array_slice($this->fields, $offset + 1);

        $this->fields = $beforeFields;

        $this->add($fieldName, $type, $options, $modify);

        $this->fields += $afterFields;

        return $this;
    }

    public function compose($class, array|Arrayable $options = [], bool $modify = false): static
    {
        if ($options instanceof Arrayable) {
            $options = $options->toArray();
        }

        $options['class'] = $class;

        // If we pass a ready-made form just extract the fields.
        if ($class instanceof Form) {
            $fields = $class->getFields();
        } elseif ($class instanceof Fields\ChildFormType) {
            $fields = $class->getForm()->getFields();
        } elseif (is_string($class)) {
            // If it's a string of a class make it the usual way.
            $options['model'] = $this->model;
            $options['name'] = $this->name;

            $form = $this->formBuilder->create($class, $options);
            $fields = $form->getFields();
        } else {
            throw new InvalidArgumentException(
                "[{$class}] is invalid. Please provide either a full class name, Form or ChildFormType"
            );
        }

        foreach ($fields as $field) {
            $this->addField($field, $modify);
        }

        return $this;
    }

    public function getFields(): array
    {
        return $this->fields;
    }

    public function remove(string|array $names): static
    {
        foreach (is_array($names) ? $names : func_get_args() as $name) {
            if ($this->has($name)) {
                unset($this->fields[$name]);
            }
        }

        return $this;
    }

    public function modify(string $name, string $type = 'text', array|Arrayable $options = [], bool $overwriteOptions = false): static
    {
        if ($options instanceof Arrayable) {
            $options = $options->toArray();
        }

        // If we don't want to overwrite options, we merge them with old options.
        if ($overwriteOptions === false && $this->has($name)) {
            $options = $this->formHelper->mergeOptions(
                $this->getField($name)->getOptions(),
                $options
            );
        }

        return $this->add($name, $type, $options, true);
    }

    public function renderForm(array $options = [], bool $showStart = true, bool $showFields = true, bool $showEnd = true): string
    {
        return $this->render($options, $this->fields, $showStart, $showFields, $showEnd);
    }

    protected function render(array $options, array $fields, bool $showStart, bool $showFields, bool $showEnd): string
    {
        $formOptions = $this->buildFormOptionsForFormBuilder(
            $this->formHelper->mergeOptions($this->formOptions, $options)
        );

        $this->setupNamedModel();

        return $this->formHelper->getView()
            ->make($this->getTemplate())
            ->with(compact('showStart', 'showFields', 'showEnd'))
            ->with('formOptions', $formOptions)
            ->with('fields', $fields)
            ->with('model', $this->getModel())
            ->with('exclude', $this->exclude)
            ->with('form', $this)
            ->render();
    }

    protected function buildFormOptionsForFormBuilder(array $formOptions): array
    {
        $reserved = ['method', 'url', 'route', 'action', 'files'];
        $formAttributes = Arr::get($formOptions, 'attr', []);

        // move string value to `attr` to maintain backward compatibility
        foreach ($formOptions as $key => $formOption) {
            if (! in_array($formOption, $reserved) && is_string($formOption)) {
                $formAttributes[$key] = $formOption;
            }
        }

        return array_merge(
            $formAttributes,
            Arr::only($formOptions, $reserved)
        );
    }

    public function only(string|array $fieldNames): static
    {
        $newFields = [];

        foreach (is_array($fieldNames) ? $fieldNames : func_get_args() as $fieldName) {
            $newFields[$fieldName] = $this->getField($fieldName);
        }

        $this->fields = $newFields;

        return $this;
    }

    public function getField(string $name): FormField
    {
        if (! $this->has($name)) {
            $this->fieldDoesNotExist($name);
        }

        return $this->fields[$name];
    }

    protected function fieldDoesNotExist(string $name)
    {
        throw new InvalidArgumentException('Field [' . $name . '] does not exist in ' . get_class($this));
    }

    protected function setupNamedModel(): bool
    {
        if (! $this->getModel() || ! $this->getName()) {
            return false;
        }

        $dotName = $this->getNameKey();
        $model = $this->formHelper->convertModelToArray($this->getModel());
        $isCollectionFormModel = (bool) preg_match('/^.*\.\d+$/', $dotName);
        $isCollectionPrototype = str_contains($dotName, '__NAME__');

        if (! Arr::get($model, $dotName) && ! $isCollectionFormModel && ! $isCollectionPrototype) {
            $newModel = [];
            Arr::set($newModel, $dotName, $model);
            $this->model = $newModel;

            return true;
        }

        return false;
    }

    public function getModel(): array|Model
    {
        return $this->model;
    }

    public function setModel(array|Model $model): static
    {
        $this->model = $model;

        $this->rebuildForm();

        return $this;
    }

    public function getNameKey(): string
    {
        if ($this->name === null) {
            return '';
        }

        return $this->formHelper->transformToDotSyntax($this->name);
    }

    protected function getTemplate(): string
    {
        return $this->getTemplatePrefix() . $this->getFormOption('template', $this->getConfig('form'));
    }

    public function getTemplatePrefix(): ?string
    {
        if ($this->templatePrefix !== null) {
            return $this->templatePrefix;
        }

        return $this->getConfig('template_prefix');
    }

    public function setTemplatePrefix(?string $prefix): static
    {
        $this->templatePrefix = (string) $prefix;

        return $this;
    }

    public function getConfig(?string $key = null, $default = null)
    {
        return $this->formHelper->getConfig($key, $default, $this->formConfig);
    }

    public function getFormOption(string $option, $default = null)
    {
        return Arr::get($this->formOptions, $option, $default);
    }

    public function renderRest(bool $showFormEnd = true, bool $showFields = true): string
    {
        $fields = $this->getUnrenderedFields();

        return $this->render([], $fields, false, $showFields, $showFormEnd);
    }

    protected function getUnrenderedFields(): array
    {
        $unrenderedFields = [];

        foreach ($this->fields as $field) {
            if (! $field->isRendered()) {
                $unrenderedFields[] = $field;
            }
        }

        return $unrenderedFields;
    }

    public function renderUntil(string $fieldName, bool $showFormEnd = true, bool $showFields = true): string
    {
        if (! $this->has($fieldName)) {
            $this->fieldDoesNotExist($fieldName);
        }

        $fields = $this->getUnrenderedFields();

        $i = 1;
        foreach ($fields as $value) {
            if ($value->getRealName() == $fieldName) {
                break;
            }
            $i++;
        }

        $fields = array_slice($fields, 0, $i, true);

        return $this->render([], $fields, false, $showFields, $showFormEnd);
    }

    public function getFormOptions(): array
    {
        return $this->formOptions;
    }

    public function setFormOptions(array $formOptions): static
    {
        $this->formOptions = $this->formHelper->mergeOptions($this->formOptions, $formOptions);
        $this->checkIfNamedForm();
        $this->pullFromOptions('data', 'addData');
        $this->pullFromOptions('model', 'setupModel');
        $this->pullFromOptions('errors_enabled', 'setErrorsEnabled');
        $this->pullFromOptions('client_validation', 'setClientValidationEnabled');
        $this->pullFromOptions('template_prefix', 'setTemplatePrefix');
        $this->pullFromOptions('language_name', 'setLanguageName');
        $this->pullFromOptions('translation_template', 'setTranslationTemplate');

        return $this;
    }

    protected function checkIfNamedForm(): void
    {
        if ($this->getFormOption('name')) {
            $this->name = Arr::pull($this->formOptions, 'name', $this->name);
        }
    }

    protected function pullFromOptions(string $name, string $method): void
    {
        if (Arr::get($this->formOptions, $name) !== null) {
            $this->{$method}(Arr::pull($this->formOptions, $name));
        }
    }

    public function setFormOption(string $option, $value): static
    {
        $this->formOptions[$option] = $value;

        return $this;
    }

    public function getMethod(): string
    {
        return $this->formOptions['method'];
    }

    public function setMethod(string $method): static
    {
        $this->formOptions['method'] = $method;

        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->formOptions['url'];
    }

    public function setUrl(?string $url): static
    {
        $this->formOptions['url'] = $url;

        return $this;
    }

    public function __get($name)
    {
        if ($this->has($name)) {
            return $this->getField($name);
        }
    }

    public function __isset($name)
    {
        return $this->has($name);
    }

    public function setEventDispatcher(EventDispatcher $eventDispatcher): static
    {
        $this->eventDispatcher = $eventDispatcher;

        return $this;
    }

    public function getFormHelper(): FormHelper
    {
        return $this->formHelper;
    }

    public function setFormHelper(FormHelper $formHelper): static
    {
        $this->formHelper = $formHelper;

        return $this;
    }

    public function addCustomField(string $name, string $class): static
    {
        if ($this->rebuilding && $this->formHelper->hasCustomField($name)) {
            return $this;
        }

        $this->formHelper->addCustomField($name, $class);

        return $this;
    }

    public function haveErrorsEnabled(): bool
    {
        return $this->showFieldErrors;
    }

    public function setErrorsEnabled(bool $enabled): static
    {
        $this->showFieldErrors = $enabled;

        return $this;
    }

    public function clientValidationEnabled(): bool
    {
        return $this->clientValidationEnabled;
    }

    public function setClientValidationEnabled(bool $enable): static
    {
        $this->clientValidationEnabled = $enable;

        return $this;
    }

    public function getData(?string $name = null, $default = null): mixed
    {
        if ($name === null) {
            return $this->data;
        }

        return Arr::get($this->data, $name, $default);
    }

    public function setData(string $name, $data): static
    {
        $this->data[$name] = $data;

        return $this;
    }

    public function addData(array $data): static
    {
        foreach ($data as $key => $value) {
            $this->setData($key, $value);
        }

        return $this;
    }

    public function getLanguageName(): string
    {
        return $this->languageName;
    }

    public function setLanguageName(?string $prefix): static
    {
        $this->languageName = (string) $prefix;

        return $this;
    }

    public function getTranslationTemplate(): string
    {
        return $this->translationTemplate;
    }

    public function setTranslationTemplate(?string $template): static
    {
        $this->translationTemplate = (string) $template;

        return $this;
    }

    public function getFormBuilder(): ?FormBuilder
    {
        return $this->formBuilder;
    }

    public function setFormBuilder(FormBuilder $formBuilder): static
    {
        $this->formBuilder = $formBuilder;

        return $this;
    }

    public function getValidator(): ?Validator
    {
        return $this->validator;
    }

    public function setValidator(ValidatorFactory $validator): static
    {
        $this->validatorFactory = $validator;

        return $this;
    }

    public function exclude(array $fields): static
    {
        $this->exclude = array_merge($this->exclude, $fields);

        return $this;
    }

    public function disableFields(): static
    {
        foreach ($this->fields as $field) {
            $field->disable();
        }

        return $this;
    }

    public function enableFields(): static
    {
        foreach ($this->fields as $field) {
            $field->enable();
        }

        return $this;
    }

    public function redirectIfNotValid(?string $destination = null): void
    {
        if (! $this->isValid()) {
            $response = redirect($destination);

            if (is_null($destination)) {
                $response = $response->back();
            }

            $response = $response->withErrors($this->getErrors(), $this->getErrorBag())->withInput();

            throw new HttpResponseException($response);
        }
    }

    public function isValid(): bool
    {
        if (! $this->validator) {
            $this->validate();
        }

        $isValid = ! $this->validator->fails();

        $this->formHelper->alterValid($this, $this, $isValid);

        $this->eventDispatcher->dispatch(new AfterFormValidation($this, $this->validator, $isValid));

        return $isValid;
    }

    public function validate(array $validationRules = [], array $messages = []): ?Validator
    {
        $fieldRules = $this->formHelper->mergeFieldsRules($this->fields);
        $rules = array_merge($fieldRules->getRules(), $validationRules);
        $messages = array_merge($fieldRules->getMessages(), $messages);

        $this->validator = $this->validatorFactory->make($this->getRequest()->all(), $rules, $messages);
        $this->validator->setAttributeNames($fieldRules->getAttributes());

        $this->eventDispatcher->dispatch(new BeforeFormValidation($this, $this->validator));

        return $this->validator;
    }

    public function getRules(array $overrideRules = []): array
    {
        $fieldRules = $this->formHelper->mergeFieldsRules($this->fields);

        return array_merge($fieldRules->getRules(), $overrideRules);
    }

    public function getRequest(): Request
    {
        return $this->request;
    }

    public function setRequest(Request $request): static
    {
        $this->request = $request;

        return $this;
    }

    public function alterValid(Form $mainForm, bool &$isValid): array
    {
        // return ['name' => ['Some other error about the Name field.']];

        return [];
    }

    public function getErrors(): array
    {
        if (! $this->validator instanceof Validator) {
            throw new InvalidArgumentException(
                sprintf(
                    'Form %s was not validated. To validate it, call "isValid" method before retrieving the errors',
                    get_class($this)
                )
            );
        }

        return $this->validator->getMessageBag()->getMessages();
    }

    public function getErrorBag(): string
    {
        return $this->errorBag;
    }

    public function getFieldValues(bool $withNulls = true): array
    {
        $request_values = $this->getRequest()->all();

        $values = [];
        foreach ($this->getAllAttributes() as $attribute) {
            $value = Arr::get($request_values, $attribute);
            if ($withNulls || $value !== null) {
                Arr::set($values, $attribute, $value);
            }
        }

        // If this form is a child form, cherry-pick a part
        if ($this->getName()) {
            $prefix = $this->getNameKey();
            $values = Arr::get($values, $prefix);
        }

        // Allow form-specific value alters
        $this->formHelper->alterFieldValues($this, $values);

        return $values;
    }

    public function getAllAttributes(): array
    {
        return $this->formHelper->mergeAttributes($this->fields);
    }

    public function alterFieldValues(array &$values)
    {
    }

    public function filterFields(): static
    {
        // If filtering is unlocked/allowed we can start with filtering process.
        if (! $this->isFilteringLocked()) {
            $filters = array_filter($this->getFilters());

            if (count($filters)) {
                $dotForm = $this->getNameKey();

                $request = $this->getRequest();
                $requestData = $request->all();

                foreach ($filters as $field => $fieldFilters) {
                    $dotField = $this->formHelper->transformToDotSyntax($field);
                    $fieldData = Arr::get($requestData, $dotField);
                    if ($fieldData !== null) {
                        // Assign current Raw/Unmutated value from request.
                        $localDotField = preg_replace('#^' . preg_quote("$dotForm.", '#') . '#', '', $dotField);
                        $localBracketField = $this->formHelper->transformToBracketSyntax($localDotField);
                        $this->getField($localBracketField)->setRawValue($fieldData);
                        foreach ($fieldFilters as $filter) {
                            $filterObj = FilterResolver::instance($filter);
                            $fieldData = $filterObj->filter($fieldData);
                        }
                        Arr::set($requestData, $dotField, $fieldData);
                    }
                }

                foreach ($requestData as $name => $value) {
                    $request[$name] = $value;
                }
            }
        }

        return $this;
    }

    public function isFilteringLocked(): bool
    {
        return $this->lockFiltering;
    }

    public function getFilters(): array
    {
        $filters = [];
        foreach ($this->getFields() as $field) {
            $filters[$field->getName()] = $field->getFilters();
        }

        return $filters;
    }

    public function lockFiltering(): static
    {
        $this->lockFiltering = true;

        return $this;
    }

    public function unlockFiltering(): static
    {
        $this->lockFiltering = false;

        return $this;
    }

    public function getRawValues(): array
    {
        $rawValues = [];
        foreach ($this->getFields() as $field) {
            $rawValues[$field->getName()] = $field->getRawValue();
        }

        return $rawValues;
    }

    protected function setupModel(array|Model $model): static
    {
        $this->model = $model;
        $this->setupNamedModel();

        return $this;
    }
}
