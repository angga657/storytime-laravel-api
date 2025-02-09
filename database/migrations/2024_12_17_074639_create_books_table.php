<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::create('books', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->unsignedBigInteger('id_user');
            $table->unsignedBigInteger('id_category');
            $table->text('content');
            $table->text('image');
            $table->timestamps();

            $table->foreign('id_user')->references('id')->on('users');
            $table->foreign('id_category')->references('id')->on('categories');
        });
        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('books');
    }
};
