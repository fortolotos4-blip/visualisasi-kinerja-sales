<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePembayaranTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pembayaran', function (Blueprint $table) {
    $table->bigIncrements('id');
    $table->unsignedBigInteger('sales_order_id');
    $table->date('tanggal_pembayaran');
    $table->integer('jumlah');
    $table->string('metode_pembayaran'); // transfer, tunai, dll
    $table->string('bukti')->nullable(); // bisa berupa gambar/foto bukti
    $table->enum('status', ['pending', 'diterima', 'ditolak'])->default('pending');
    $table->text('catatan')->nullable();
    $table->timestamps();

    $table->foreign('sales_order_id')->references('id')->on('sales_orders')->onDelete('cascade');
});

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('pembayaran');
    }
}
