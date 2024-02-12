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
        Schema::create('articles', function (Blueprint $table) {

            $table->id();
            $table->string('title', 255);
            $table->string('slug', 260)->unique();

            $table->mediumText('text');
            $table->string('text_shortly', 255)->nullable();

            $table->integer('creator_id')->unsigned();
            $table->foreign('creator_id')->references('id')->on('users')->onDelete('CASCADE');

            $table->boolean('published')->default(false);

            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->nullable();
            $table->index(['created_at'], 'articles_created_at_index');

            $table->index(['creator_id', 'published', 'title'], 'articles_creator_id_published_title_index');
        });

        Artisan::call('db:seed', array('--class' => 'ArticleSeeder'));
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('articles');
    }
};
