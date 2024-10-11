@extends(Theme::getThemeNamespace() . '::views.ecommerce.customers.master')
@section('content')
    <style>
        .dropzone {
            border: 2px dashed var(--primary-color);
        }
    </style>

    <div class="form__header">
        <h3>{{ SeoHelper::getTitle() }}</h3>

        {!!
            $form
            ->modify(
                'submit',
                'submit',
                Botble\Base\Forms\FieldOptions\ButtonFieldOption::make()
                    ->label(__('Register'))
                    ->cssClass('ps-btn'),
                true
            )
            ->renderForm()
        !!}
    </div>
@endsection
