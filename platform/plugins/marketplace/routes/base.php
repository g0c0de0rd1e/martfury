<?php

use Botble\Base\Facades\AdminHelper;
use Botble\Marketplace\Http\Controllers\WithdrawalInvoiceController;
use Illuminate\Support\Facades\Route;

AdminHelper::registerRoutes(function () {
    Route::group(['namespace' => 'Botble\Marketplace\Http\Controllers'], function () {
        Route::group(['prefix' => 'marketplaces', 'as' => 'marketplace.'], function () {
            Route::group(['prefix' => 'stores', 'as' => 'store.'], function () {
                Route::resource('', 'StoreController')->parameters(['' => 'store']);
                Route::post('update-tax-info/{store}', [
                    'as' => 'update-tax-info',
                    'uses' => 'StoreController@updateTaxInformation',
                    'permission' => 'marketplace.store.edit',
                ]);
                Route::post('update-payout-info/{store}', [
                    'as' => 'update-payout-info',
                    'uses' => 'StoreController@updatePayoutInformation',
                    'permission' => 'marketplace.store.edit',
                ]);

                Route::get('view/{id}', [
                    'as' => 'view',
                    'uses' => 'StoreRevenueController@view',
                ])->wherePrimaryKey();

                Route::group(['prefix' => 'revenues', 'as' => 'revenue.'], function () {
                    Route::match(['GET', 'POST'], 'list/{id}', [
                        'as' => 'index',
                        'uses' => 'StoreRevenueController@index',
                        'permission' => 'marketplace.store.view',
                    ])->wherePrimaryKey();

                    Route::post('create/{id}', [
                        'as' => 'create',
                        'uses' => 'StoreRevenueController@store',
                    ])->wherePrimaryKey();
                });
            });

            Route::group(['prefix' => 'withdrawals', 'as' => 'withdrawal.'], function () {
                Route::resource('', 'WithdrawalController')
                    ->parameters(['' => 'withdrawal'])
                    ->except([
                        'create',
                        'store',
                    ]);

                Route::get('{withdrawal}/invoice', [WithdrawalInvoiceController::class, '__invoke'])
                    ->name('invoice');
            });

            Route::get('settings', [
                'as' => 'settings',
                'uses' => 'Settings\MarketplaceSettingController@edit',
            ]);

            Route::put('settings', [
                'as' => 'settings.update',
                'uses' => 'Settings\MarketplaceSettingController@update',
                'permission' => 'marketplace.settings',
            ]);

            Route::group(['prefix' => 'unverified-vendors', 'as' => 'unverified-vendors.'], function () {
                Route::match(['GET', 'POST'], '/', [
                    'as' => 'index',
                    'uses' => 'UnverifiedVendorController@index',
                ]);

                Route::get('view/{id}', [
                    'as' => 'view',
                    'uses' => 'UnverifiedVendorController@view',
                    'permission' => 'marketplace.unverified-vendors.edit',
                ])->wherePrimaryKey();

                Route::post('approve/{id}', [
                    'as' => 'approve-vendor',
                    'uses' => 'UnverifiedVendorController@approveVendor',
                    'permission' => 'marketplace.unverified-vendors.edit',
                ])->wherePrimaryKey();

                Route::post('reject/{id}', [
                    'as' => 'reject-vendor',
                    'uses' => 'UnverifiedVendorController@rejectVendor',
                    'permission' => 'marketplace.unverified-vendors.edit',
                ])->wherePrimaryKey();

                Route::get('download-certificate/{id}', [
                    'as' => 'download-certificate',
                    'uses' => 'UnverifiedVendorController@downloadCertificate',
                    'permission' => 'marketplace.unverified-vendors.edit',
                ])->wherePrimaryKey();

                Route::get('download-government-id/{id}', [
                    'as' => 'download-government-id',
                    'uses' => 'UnverifiedVendorController@downloadGovernmentId',
                    'permission' => 'marketplace.unverified-vendors.edit',
                ])->wherePrimaryKey();
            });

            Route::group(['prefix' => 'vendors', 'as' => 'vendors.'], function () {
                Route::match(['GET', 'POST'], '/', [
                    'as' => 'index',
                    'uses' => 'VendorController@index',
                ]);
            });

            Route::group([
                'prefix' => 'reports',
                'as' => 'reports.',
                'permission' => 'marketplace.reports',
                'controller' => 'ReportController',
            ], function () {
                Route::get('', [
                    'as' => 'index',
                    'uses' => 'index',
                ]);

                Route::post('store-revenues', [
                    'as' => 'store-revenues',
                    'uses' => 'getStoreRevenues',
                ]);
            });
        });

        Route::group(['prefix' => 'ecommerce/products', 'as' => 'products.'], function () {
            Route::post('approve-product/{id}', [
                'as' => 'approve-product',
                'uses' => 'ProductController@approveProduct',
                'permission' => 'products.edit',
            ])->wherePrimaryKey();
        });
    });
});