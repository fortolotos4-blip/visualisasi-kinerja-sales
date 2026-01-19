<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('penjualan', function (Blueprint $table) {
            // Tambah kolom sales_order_id
            $table->unsignedBigInteger('sales_order_id')->nullable()->after('id');

            // Tambah foreign key ke tabel sales_orders
            $table->foreign('sales_order_id')
                  ->references('id')
                  ->on('sales_orders')
                  ->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::table('penjualan', function (Blueprint $table) {
            // Hapus relasi dan kolom kalau rollback
            $table->dropForeign(['sales_order_id']);
            $table->dropColumn('sales_order_id');
        });
    }
};
