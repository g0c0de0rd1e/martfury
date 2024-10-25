<?php

namespace App\Policies;

use App\Models\Chat;
use Botble\Ecommerce\Models\Customer;
use Botble\Marketplace\Models\VendorInfo;
use Illuminate\Auth\Access\HandlesAuthorization;

class ChatPolicy
{
    use HandlesAuthorization;

    public function view($user, Chat $chat)
    {
        if ($user instanceof Customer) {
            return $chat->user_id == $user->id;
        }

        if ($user instanceof VendorInfo) {
            return $chat->customer_id == $user->id;
        }

        return false;
    }

    public function update($user, Chat $chat)
    {
        if ($user instanceof Customer) {
            return $chat->user_id == $user->id;
        }

        if ($user instanceof VendorInfo) {
            return $chat->customer_id == $user->id;
        }

        return false;
    }

    public function delete($user, Chat $chat)
    {
        if ($user instanceof Customer) {
            return $chat->user_id == $user->id;
        }

        if ($user instanceof VendorInfodor) {
            return $chat->customer_id == $user->id;
        }

        return false;
    }
}
