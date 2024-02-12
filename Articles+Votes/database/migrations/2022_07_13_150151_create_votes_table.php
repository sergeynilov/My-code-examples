<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateVotesTable extends Migration
{
    public function up()
    {
        Schema::create('votes', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255)->unique();
            $table->string('slug', 260)->unique();
            $table->mediumText('description');

            $table->integer('creator_id')->unsigned();
            $table->foreign('creator_id')->references('id')->on('users')->onDelete('CASCADE');

            $table->smallInteger('vote_category_id')->unsigned();
            $table->foreign('vote_category_id')->references('id')->on('vote_categories')->onDelete('CASCADE');

            $table->boolean('is_quiz')->default(false);
            $table->boolean('is_homepage')->default(false);
            $table->enum('status', ['N', 'A', 'I'])->comment(' N=>New,  A=>Active, I=>Inactive');
            $table->integer('ordering')->unsigned();

            $table->string('meta_description', 255)->nullable();
            $table->json('meta_keywords')->nullable();

            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->nullable();

            $table->index(['created_at'], 'votes_created_at_index');
            $table->index(['is_quiz', 'status'], 'votes_is_quiz_status_index');
            $table->index(['ordering', 'status'], 'votes_ordering_status_index');
            $table->index(['is_homepage', 'status'], 'votes_is_homepage_status_index');

            $table->index(['creator_id', 'status', 'name'], 'votes_creator_id_status_name_index');
            $table->index(['vote_category_id', 'status', 'name'], 'votes_vote_category_id_status_name_index');

        });

        Artisan::call('db:seed', array('--class' => 'votesInitData'));

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('votes');
    }
}
