<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDeliveryPriceToShopDeliveryZipcodesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('shop_delivery_zipcodes', function (Blueprint $table) {
            $table->decimal('delivery_price', 8, 2)->after('zip_code')->nullable(); 
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('shop_delivery_zipcodes', function (Blueprint $table) {
        	$table->dropColumn('delivery_price');
        });
    }
}
