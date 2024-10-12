<?php

namespace Kris\LaravelFormBuilder;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\View\Compilers\BladeCompiler;
use InvalidArgumentException;
use Kris\LaravelFormBuilder\Events\FormComponentRegistered;
use Kris\LaravelFormBuilder\Events\FormComponentRegistering;
use Kris\LaravelFormBuilder\Supports\FormBuilder as LaravelForm;
use Kris\LaravelFormBuilder\Supports\HtmlBuilder;
use Kris\LaravelFormBuilder\Traits\ValidatesWhenResolved;

class FormBuilderServiceProvider extends ServiceProvider
{
    protected const FORM_ABSTRACT = 'form';
    protected const BUILDER_ABSTRACT = 'laravel-form-builder';
    protected const HELPER_ABSTRACT = 'laravel-form-helper';

    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/config.php',
            'laravel-form-builder'
        );

        $this->app->singleton('form', function ($app) {
            $form = new LaravelForm(
                $app['html'],
                $app['url'],
                $app['view'],
                $app['session.store']->token(),
                $app['request']
            );

            $app['events']->dispatch(new FormComponentRegistering($form));

            $app['events']->dispatch(new FormComponentRegistered($form));

            return $form->setSessionStore($app['session.store']);
        });

        $this->app->singleton('html', function ($app) {
            return new HtmlBuilder($app['url'], $app['view']);
        });

        $this->app->alias('html', HtmlBuilder::class);
        $this->app->alias('form', LaravelForm::class);

        $this->registerBladeDirectives();
    }

    protected function registerBladeDirectives(): void
    {
        $this->app->afterResolving('blade.compiler', function (BladeCompiler $bladeCompiler) {
            $namespaces = [
                'Html' => get_class_methods(HtmlBuilder::class),
                'Form' => get_class_methods(LaravelForm::class),
            ];

            $directives = [
                'entities',
                'decode',
                'script',
                'style',
                'image',
                'favicon',
                'link',
                'secureLink',
                'linkAsset',
                'linkSecureAsset',
                'linkRoute',
                'linkAction',
                'mailto',
                'email',
                'ol',
                'ul',
                'dl',
                'meta',
                'tag',
                'open',
                'model',
                'close',
                'token',
                'label',
                'input',
                'text',
                'password',
                'hidden',
                'email',
                'tel',
                'number',
                'date',
                'datetime',
                'datetimeLocal',
                'time',
                'url',
                'file',
                'textarea',
                'select',
                'selectRange',
                'selectYear',
                'selectMonth',
                'getSelectOption',
                'checkbox',
                'radio',
                'reset',
                'image',
                'color',
                'submit',
                'button',
                'old',
            ];

            foreach ($namespaces as $namespace => $methods) {
                foreach ($methods as $method) {
                    if (! in_array($method, $directives)) {
                        continue;
                    }

                    $snakeMethod = Str::snake($method);
                    $directive = strtolower($namespace) . '_' . $snakeMethod;

                    $bladeCompiler->directive($directive, function ($expression) use ($namespace, $method) {
                        return "<?php echo $namespace::$method($expression); ?>";
                    });
                }
            }
        });
    }

    protected function registerFormHelper(): void
    {
        $abstract = static::HELPER_ABSTRACT;

        $formHelperClass = $this->getFormHelperClass();

        $this->app->singleton($abstract, function ($app) use ($formHelperClass) {
            $configuration = $app['config']->get('laravel-form-builder');

            return new $formHelperClass($app['view'], $app['translator'], $configuration);
        });

        $this->app->alias($abstract, $formHelperClass);
        if ($formHelperClass != FormHelper::class) {
            $this->app->alias($abstract, FormHelper::class);
        }
    }

    protected function getFormHelperClass()
    {
        $expectedClass = FormHelper::class;
        $defaultClass = FormHelper::class;

        $class = $this->app['config']->get('laravel-form-builder.form_helper_class', $defaultClass);

        if (! class_exists($class)) {
            throw new InvalidArgumentException("Class {$class} does not exist");
        }

        if ($class !== $expectedClass && ! is_subclass_of($class, $expectedClass)) {
            throw new InvalidArgumentException("Class {$class} must extend " . $expectedClass);
        }

        return $class;
    }

    protected function registerFormBuilder(): void
    {
        $abstract = static::BUILDER_ABSTRACT;

        $formBuilderClass = $this->getFormBuilderClass();

        $this->app->singleton($abstract, function ($app) use ($formBuilderClass) {
            $formBuilder = new $formBuilderClass($app, $app[static::HELPER_ABSTRACT], $app['events']);
            $formBuilder->setFormClass($this->getPlainFormClass());

            return $formBuilder;
        });

        $this->app->alias($abstract, $formBuilderClass);
        if ($formBuilderClass != FormBuilder::class) {
            $this->app->alias($abstract, FormBuilder::class);
        }

        $this->app->afterResolving(Form::class, function ($object, $app) use ($abstract) {
            $request = $app->make('request');

            if (in_array(ValidatesWhenResolved::class, class_uses($object), true) && $request->method() !== 'GET') {
                $form = $app->make($abstract)->setDependenciesAndOptions($object);
                $form->buildForm();
                $form->redirectIfNotValid();
            }
        });
    }

    protected function getFormBuilderClass()
    {
        $expectedClass = FormBuilder::class;
        $defaultClass = FormBuilder::class;

        $class = $this->app['config']->get('laravel-form-builder.form_builder_class', $defaultClass);

        if (! class_exists($class)) {
            throw new InvalidArgumentException("Class {$class} does not exist");
        }

        if ($class !== $expectedClass && ! is_subclass_of($class, $expectedClass)) {
            throw new InvalidArgumentException("Class {$class} must extend " . $expectedClass);
        }

        return $class;
    }

    protected function getPlainFormClass()
    {
        return $this->app['config']->get('laravel-form-builder.plain_form_class', Form::class);
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'laravel-form-builder');

        $this->publishes([
            __DIR__ . '/../resources/views' => base_path('resources/views/vendor/laravel-form-builder'),
            __DIR__ . '/../config/config.php' => config_path('laravel-form-builder.php'),
        ]);

        $this->registerFormHelper();
        $this->registerFormBuilder();

        $this->app->afterResolving('form', function (LaravelForm $form) {
            $form->macro('customLabel', function ($name, $value, $options = [], $escapeHtml = true) use ($form) {
                if (isset($options['for']) && $for = $options['for']) {
                    unset($options['for']);

                    return $form->label($for, $value, $options, $escapeHtml);
                }
            });
        });
    }

    public function provides(): array
    {
        return ['laravel-form-builder', 'html', 'form', HtmlBuilder::class, FormBuilder::class];
    }
}
