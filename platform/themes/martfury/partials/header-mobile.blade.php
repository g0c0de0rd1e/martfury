<header class="header header--mobile" data-sticky="true">
    <div class="navigation--mobile">
        <div class="navigation__left">
            <a class="ps-logo" href="{{ BaseHelper::getHomepageUrl() }}">
                {!! Theme::getLogoImage(['style' => 'max-height: 40px']) !!}
            </a>
        </div>
        @if (is_plugin_active('ecommerce'))
            <div class="navigation__right">
                <div class="header__actions">
                    {!! apply_filters('before_theme_header_mobile_actions', null) !!}
                    <div class="ps-cart--mini">
                        <a class="header__extra btn-shopping-cart" href="{{ route('public.cart') }}">
                            <i class="icon-bag2"></i><span><i>{{ Cart::instance('cart')->count() }}</i></span>
                        </a>
                        <div class="ps-cart--mobile">
                            {!! Theme::partial('cart') !!}
                        </div>
                    </div>
                    {!! apply_filters('after_theme_header_mobile_actions', null) !!}
                    <div class="ps-block--user-header">
                        <div class="ps-block__left"><a href="{{ route('customer.overview') }}"><i class="icon-user"></i></a></div>
                    </div>
                </div>
            </div>
        @endif
    </div>
    @if (is_plugin_active('ecommerce'))
        <div class="ps-search--mobile">
            <form class="ps-form--search-mobile" action="{{ route('public.products') }}" data-ajax-url="{{ route('public.ajax.search-products') }}" method="get">
                <div class="form-group--nest position-relative">
                    <input class="form-control input-search-product" name="q" value="{{ BaseHelper::stringify(request()->query('q')) }}" type="text" autocomplete="off" placeholder="{{ __('Search something...') }}">
                    <div class="spinner-icon">
                        <i class="fa fa-spin fa-spinner"></i>
                    </div>
                    <button type="submit"><i class="icon-magnifier"></i></button>
                    <div class="ps-panel--search-result"></div>
                </div>
            </form>
        </div>
    @endif
</header>
