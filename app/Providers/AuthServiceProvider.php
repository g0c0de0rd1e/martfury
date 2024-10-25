<?php

namespace App\Providers;

use App\Models\Chat;
use App\Policies\ChatPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Chat::class => ChatPolicy::class,
    ];

    public function boot()
    {
        $this->registerPolicies();
    }
}
