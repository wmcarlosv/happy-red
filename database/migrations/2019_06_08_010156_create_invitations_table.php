<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateInvitationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('invitations', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id')->nullable();;
            $table->unsignedBigInteger('invited_user_id')->nullable();
            $table->unsignedBigInteger('invited_user_game')->nullable();
            $table->integer('level_1')->default(0);
            $table->integer('level_2')->default(0);
            $table->integer('level_3')->default(0);
            $table->integer('level_4')->default(0);
            $table->integer('level_5')->default(0);
            $table->boolean('status')->default(0);
            $table->boolean('migrated')->default(0);
            $table->string('description')->nullable();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('invited_user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('invited_user_game')->references('id')->on('games')->onDelete('cascade');
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
        Schema::dropIfExists('invitations');
    }
}
