<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('article_vote', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->references('id')->on('articles')->onUpdate('RESTRICT')->onDelete('CASCADE');
            $table->foreignId('vote_id')->references('id')->on('votes')->onUpdate('RESTRICT')->onDelete('CASCADE');

            $table->boolean('active')->default(false);
            $table->date('expired_at')->nullable();

            $table->integer('supervisor_id')->nullable()->unsigned();
            $table->foreign('supervisor_id')->references('id')->on('users')->onDelete('CASCADE');
            $table->mediumText('supervisor_notes')->nullable();

            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->nullable();

            $table->unique(['vote_id', 'article_id'], 'article_vote_vote_id_article_id_index');
            $table->index(['vote_id', 'article_id', 'active', 'expired_at'], 'article_vote_vote_id_article_id_active_expired_at_index');
            $table->index([ 'expired_at', 'active',], 'article_vote_expired_at_active_index');
            $table->index(['created_at'], 'article_vote_created_at_index');
        });

        Artisan::call('db:seed', array('--class' => 'articleVotesWithInitData'));


    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('article_vote');
    }
};
