<div class="login-options">
    <div class="login-options-title">
        <p>{{ __('Login with social networks') }}</p>
    </div>

    <ul @class(['social-icons', 'social-login-lg' => setting('social_login_style', 'default') === 'default'])>
        @foreach (SocialService::getProviderKeys() as $item)
            @continue(! SocialService::getProviderEnabled($item))

            {!! apply_filters(
                'social_login_' . $item . '_render',
                view('plugins/social-login::social-login-item', ['social' => $item, 'url' => route('auth.social', isset($params) ? array_merge([$item], $params) : $item)])->render(),
                $item
            ) !!}
        @endforeach
    </ul>
</div>
