<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RemoveProdukIdFromPenjualanTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('penjualan', function (Blueprint $table) {
            // Hapus foreign key constraint dulu (kalau ada)
            if (Schema::hasColumn('penjualan', 'produk_id')) {
                $table->dropForeign(['produk_id']);
                $table->dropColumn('produk_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('penjualan', function (Blueprint $table) {
            $table->unsignedBigInteger('produk_id')->nullable();

            // Kalau mau menambahkan kembali relasi
            $table->foreign('produk_id')->references('id')->on('produk')->onDelete('set null');
        });
    }
}
