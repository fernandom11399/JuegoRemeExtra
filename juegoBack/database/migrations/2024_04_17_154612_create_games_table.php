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
        Schema::create('games', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_1');
            $table->foreign('user_1')->references('id')->on('users');
            $table->integer('boad1')->default(5);
            $table->unsignedBigInteger('user_2')->nullable();
            $table->foreign('user_2')->references('id')->on('users');
            $table->integer('boad2')->default(5);
            $table->boolean("is_active")->default(false);
            $table->date('start_at')->nullable();
            $table->string('won')->nullable();            
            $table->boolean("turn")->default(false);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('games');
    }
};
