<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('chats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('ec_customers');
            $table->foreignId('store_id')->constrained('mp_stores'); // Используем связывание с mp_stores через store_id
            $table->foreignId('ec_product_id')->constrained('ec_products');
            $table->text('message');
            $table->string('file_path')->nullable(); // Добавляем поле для файла
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('chats');
    }
};
