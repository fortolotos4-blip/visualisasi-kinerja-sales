<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateKontribusiParametersTable extends Migration
{
    public function up()
    {
        Schema::create('kontribusi_parameters', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('manager_id');
            $table->integer('periode_bulan');
            $table->integer('periode_tahun');

            // bobot %
            $table->decimal('bobot_kunjungan', 5, 2)->default(0);
            $table->decimal('bobot_penawaran', 5, 2)->default(0);
            $table->decimal('bobot_penjualan', 5, 2)->default(0);

            // target jumlah
            $table->integer('target_kunjungan')->default(0);
            $table->integer('target_penawaran')->default(0);

            //status
            $table->string('status');

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('kontribusi_parameters');
    }
}
