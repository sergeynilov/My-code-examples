<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_quiz_request_communication_channels', function (Blueprint $table) {
            $table->id();

            $table->bigInteger('user_quiz_request_id')->unsigned();
            $table->foreign('user_quiz_request_id', 'user_quiz_request_communication_channel_foreign')->references('id')->on('user_quiz_requests')->onUpdate('RESTRICT')->onDelete('CASCADE');

            $table->enum('type', ['S', 'P', 'T'])->comment('  S => Skype name, P => Phone number, T => Telegram channel');
            $table->string('channel', 255);

            $table->index(['user_quiz_request_id', 'type'], 'user_quiz_request_communication_channels_indices');
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
        Schema::dropIfExists('user_quiz_request_communication_channels');
    }
};
