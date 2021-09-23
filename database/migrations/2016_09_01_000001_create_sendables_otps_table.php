<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSendablesOtpsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(SendablesHelpers::getOtpTableName(), function (Blueprint $table) {
            $table->id();
            $table->string('mobile')->index();
            $table->string('code');
            $table->string('otp_user_id', 300)->nullable();
            $table->string('otp_user_token', 300)->nullable();
            $table->timestamp('verified_at')->nullable();
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
        Schema::dropIfExists(SendablesHelpers::getOtpTableName());
    }
}
