<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSalesOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sales_orders', function (Blueprint $table) {
    $table->bigIncrements('id');
    $table->string('nomor_so')->unique();
    $table->date('tanggal');
    $table->unsignedBigInteger('sales_id');
    $table->unsignedBigInteger('customer_id');
    $table->unsignedBigInteger('produk_id');
    $table->integer('jumlah');
    $table->integer('harga_satuan');
    $table->string('status')->default('pending');
    $table->text('keterangan')->nullable();
    $table->timestamps();

    $table->foreign('sales_id')->references('id')->on('sales')->onDelete('cascade');
    $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
    $table->foreign('produk_id')->references('id')->on('produk')->onDelete('cascade');
});
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sales_orders');
    }
}
