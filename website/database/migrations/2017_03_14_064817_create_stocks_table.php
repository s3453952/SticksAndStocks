<?php

/**
 * Created by: Paul Davidson.
 * Authors: Paul Davidson and Josh Gerlach
 */

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateStocksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('stocks', function (Blueprint $table) {
            $table->increments('id');
			      $table->string('stock_symbol');
            $table->string('stock_name');
			      $table->float('current_price',8,2)->default(0.00);
			      $table->json('history')->nullable();
            $table->string('group')->default("n/a");
            $table->json('top_lists')->nullable();
            $table->string('market');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('stocks');
    }
}
