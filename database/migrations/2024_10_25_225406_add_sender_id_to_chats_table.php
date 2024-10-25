<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::table('chats', function (Blueprint $table) {
            $table->unsignedBigInteger('sender_id')->after('id')->nullable();
            $table->foreign('sender_id')->references('id')->on('ec_customers')->onDelete('cascade');
        });
    }

    public function down() {
        Schema::table('chats', function (Blueprint $table) {
            $table->dropForeign(['sender_id']);
            $table->dropColumn('sender_id');
        });
    }
};
