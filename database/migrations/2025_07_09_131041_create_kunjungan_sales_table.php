<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateKunjunganSalesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
{
    Schema::create('kunjungan_sales', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('sales_id');
        $table->unsignedBigInteger('customer_id');
        $table->date('tanggal_kunjungan');
        $table->string('tujuan');
        $table->enum('status', ['Berhasil', 'Batal', 'Tindak lanjut'])->default('Tindak lanjut');
        $table->text('keterangan')->nullable();
        $table->timestamps();

        // Foreign keys
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
        Schema::dropIfExists('kunjungan_sales');
    }
}
