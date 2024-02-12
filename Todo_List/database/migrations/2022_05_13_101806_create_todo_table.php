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
        Schema::create('todo', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->references('id')->on('users')->onDelete('CASCADE');
            $table->string('name', 100);
            $table->mediumText('description')->nullable();

            $table->boolean('completed')->default(false);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->nullable();

            $table->index(['user_id', 'completed', 'created_at'], 'todo_user_id_completed_created_at_index');
            $table->unique(['completed', 'name'], 'todo_completed_name_unique');

        });
        Artisan::call('db:seed', array('--class' => 'todoWithInitData'));
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('todo');
    }
};
