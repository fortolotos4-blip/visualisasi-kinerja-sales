<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RemoveLegacyProductColumnsFromSalesOrders extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
{
    Schema::table('sales_orders', function (Blueprint $table) {
        $table->dropColumn(['produk_id','jumlah','harga_satuan']);
    });
}


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
{
    Schema::table('sales_orders', function (Blueprint $table) {
        $table->unsignedBigInteger('produk_id')->nullable();
        $table->integer('jumlah')->nullable();
        $table->decimal('harga_satuan', 16, 2)->nullable();
    });
}

}
