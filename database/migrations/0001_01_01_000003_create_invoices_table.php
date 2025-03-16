<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInvoicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('zatca_clearance_status')->nullable();
            $table->string('zatca_clearance_id')->nullable();
            $table->timestamp('zatca_reported_at')->nullable();
            $table->boolean('zatca_compliant')->default(false);
            $table->string('zatca_xml_path')->nullable();
            $table->string('zatca_hash')->nullable();
            $table->string('uuid')->nullable();
            $table->string('qr_code')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('invoices');
    }
}