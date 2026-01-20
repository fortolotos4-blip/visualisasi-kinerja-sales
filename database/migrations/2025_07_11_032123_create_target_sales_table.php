<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTargetSalesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
    Schema::create('target_sales', function (Blueprint $table) {
    $table->bigIncrements('id');
    $table->unsignedBigInteger('sales_id');
    $table->integer('target'); // target dalam rupiah
    $table->string('bulan', 2); // format: '01' sampai '12'
    $table->string('tahun', 4); // format: '2025'
    $table->string('status');
    $table->timestamps();

    $table->foreign('sales_id')->references('id')->on('sales')->onDelete('cascade');
});


    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('target_sales');
    }
}
