<?php

namespace Botble\Marketplace\Http\Controllers\Fronts;

use Botble\Marketplace\Facades\MarketplaceHelper;
use Botble\Marketplace\Forms\Fronts\BecomeVendorForm;
use Botble\Marketplace\Http\Controllers\BaseController;
use Botble\Marketplace\Http\Requests\Fronts\BecomeVendorRequest;
use Botble\Marketplace\Models\Store;
use Botble\SeoHelper\Facades\SeoHelper;
use Botble\Slug\Facades\SlugHelper;
use Botble\Theme\Facades\Theme;
use Closure;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BecomeVendorController extends BaseController
{
    public function __construct()
    {
        $this->middleware(function (Request $request, Closure $next) {
            if (! MarketplaceHelper::isVendorRegistrationEnabled()) {
                abort(404);
            }

            return $next($request);
        });
    }

    public function index()
    {
        $customer = auth('customer')->user();
        $store = $customer->store;

        if ($customer->is_vendor) {
            if (MarketplaceHelper::getSetting('verify_vendor', 1) && ! $customer->vendor_verified_at) {
                SeoHelper::setTitle(__('Become Vendor'));

                Theme::breadcrumb()
                    ->add(__('Approving'));

                return Theme::scope('marketplace.approving-vendor', compact('store'), MarketplaceHelper::viewPath('approving-vendor', false))
                    ->render();
            }

            return redirect()->route('marketplace.vendor.dashboard');
        }

        SeoHelper::setTitle(__('Become Vendor'));

        Theme::breadcrumb()
            ->add(__('Become Vendor'), route('marketplace.vendor.become-vendor'));

        $form = BecomeVendorForm::create();

        return Theme::scope('marketplace.become-vendor', compact('form'), MarketplaceHelper::viewPath('become-vendor', false))
            ->render();
    }

    public function store(BecomeVendorRequest $request)
    {
        $customer = auth('customer')->user();

        if ($customer->is_vendor) {
            abort(404);
        }

        $existing = SlugHelper::getSlug($request->input('shop_url'), SlugHelper::getPrefix(Store::class));

        if ($existing) {
            return $this
                ->httpResponse()
                ->setError()
                ->setMessage(__('Shop URL is existing. Please choose another one!'));
        }

        event(new Registered($customer));

        return $this
            ->httpResponse()
            ->setData([
                'redirect_url' => route('marketplace.vendor.dashboard'),
            ])
            ->setMessage(__('Registered successfully!'));
    }

    public function downloadCertificate()
    {
        $customer = auth('customer')->user();

        if (! $customer->is_vendor || ! $customer->store) {
            abort(404);
        }

        $storage = Storage::disk('local');

        if (! $storage->exists($certificate = $customer->store->certificate_file)) {
            abort(404);
        }

        return response()->file($storage->path($certificate));
    }

    public function downloadGovernmentId()
    {
        $customer = auth('customer')->user();

        if (! $customer->is_vendor || ! $customer->store) {
            abort(404);
        }

        $storage = Storage::disk('local');

        if (! $storage->exists($governmentId = $customer->store->government_id_file)) {
            abort(404);
        }

        return response()->file($storage->path($governmentId));
    }
}
