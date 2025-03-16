<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNameToVendorsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->string('created_at')->nullable();
            $table->string('updated_at')->nullable();
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->string('created_at')->nullable();
            $table->string('updated_at')->nullable();
        });
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->dropColumn('created_at');
            $table->dropColumn('updated_at');
        });

        Schema::table('invoices', function (Blueprint $table) { 
            $table->dropColumn('created_at');
            $table->dropColumn('updated_at');
        });
    }
}
