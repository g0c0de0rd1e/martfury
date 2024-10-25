<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('chats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('ec_customers')->onDelete('cascade'); // Используем связывание и каскадное удаление
            $table->foreignId('store_id')->constrained('mp_stores')->onDelete('cascade'); // Используем связывание через store_id
            $table->foreignId('ec_product_id')->constrained('ec_products')->onDelete('cascade');
            $table->text('message');
            $table->string('file_path')->nullable();
            $table->timestamps();
        });
    }

    public function down() {
        Schema::dropIfExists('chats');
    }
};
