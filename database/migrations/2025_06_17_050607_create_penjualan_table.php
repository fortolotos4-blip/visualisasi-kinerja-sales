<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePenjualanTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
{
    Schema::create('penjualan', function (Blueprint $table) {
        $table->bigIncrements('id');
        $table->date('tanggal');
        $table->unsignedBigInteger('sales_id');
        $table->unsignedBigInteger('customer_id');
        $table->integer('total_harga');
        $table->timestamps();

        $table->foreign('sales_id')->references('id')->on('sales')->onDelete('cascade');
        $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
    });
}


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('penjualan');
    }
}
