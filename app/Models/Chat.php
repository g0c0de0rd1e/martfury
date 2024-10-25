<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Botble\Ecommerce\Models\Customer;
use Botble\Ecommerce\Models\Product;
use Botble\Marketplace\Models\Store;

class Chat extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'store_id',
        'ec_product_id',
        'message',
        'file_path' // Добавлено поле для пути к файлу
    ];

    public function user()
    {
        return $this->belongsTo(Customer::class, 'user_id');
    }

    public function store()
    {
        return $this->belongsTo(Store::class, 'store_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'ec_product_id');
    }

    public function messages()
    {
        return $this->hasMany(Chat::class, 'user_id', 'user_id')
                    ->where('ec_product_id', $this->ec_product_id)
                    ->where('store_id', $this->store_id);
    }
}
