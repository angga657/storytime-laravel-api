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
        //
        Schema::table('bookmarks', function (Blueprint $table) {
            // Hapus foreign key yang lama
            $table->dropForeign(['id_book']);

            // Buat foreign key baru dengan cascade delete
            $table->foreign('id_book')
                ->references('id')->on('books')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
        Schema::table('bookmarks', function (Blueprint $table) {
            $table->dropForeign(['id_book']);
            // Kembalikan foreign key sesuai kebutuhan awal Anda
            $table->foreign('id_book')
                ->references('id')->on('books');
        });
    }
};
