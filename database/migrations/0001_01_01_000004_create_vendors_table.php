<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVendorsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('vendors', function (Blueprint $table) {
            $table->id();
            $table->string('organization_name')->nullable();
            $table->boolean('zatca_onboarded')->default(false);
            $table->timestamp('zatca_onboarded_at')->nullable();
            $table->json('zatca_certificate_info')->nullable();
            $table->string('zatca_certificate_status')->nullable();
            $table->date('zatca_certificate_expiry')->nullable();
            $table->string('vat_number')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('vendors');
    }
}
