<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateStartingTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->increments('userId');
			$table->string('email', 100)->unique();
			$table->string('password', 255);
			$table->string('name', 100);
			$table->dateTime('created_at');
			$table->dateTime('updated_at');
			$table->dateTime('activatedOn')->nullable();
			$table->dateTime('lastLogin')->nullable();
			$table->boolean('admin');
        });

		Schema::create('orgs', function (Blueprint $table) {
            $table->increments('orgId');
			$table->string('name');
			$table->string('slug')->unique();
            $table->timestamps();
			$table->dateTime('start')->nullable();
			$table->dateTime('end')->nullable();
			$table->text('blocks');
			$table->boolean('sessionSubmission')->default(true);
        });

		Schema::create('orgMemberships', function (Blueprint $table) {
			$table->integer('userId')->index();
			$table->integer('orgId')->index();
			$table->boolean('admin');
			$table->primary(['userId', 'orgId']);
        });

		Schema::create('sessions', function (Blueprint $table) {
			$table->increments('sessionId');
			$table->integer('orgId')->unsigned();
			$table->string('title', 255);
			$table->string('game', 255);
			$table->integer('ownerId')->unsigned();
			$table->string('gm');
			$table->integer('typeId')->unsigned();
			$table->text('description');
			$table->string('location', 255);
			$table->text('proposedStart')->nullable();
			$table->dateTime('start');
			$table->dateTime('end');
			$table->integer('numSeats')->unsigned();
			$table->integer('seatsFilled')->unsigned();
			$table->boolean('approved');
			$table->timestamps();
			$table->softDeletes();
        });

		Schema::create('sessionTypes', function (Blueprint $table) {
			$table->increments('typeId');
			$table->integer('orgId');
			$table->string('type');
			$table->timestamps();
			$table->index('orgId');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
		Schema::dropIfExists('orgs');
		Schema::dropIfExists('orgMemberships');
		Schema::dropIfExists('sessions');
		Schema::dropIfExists('sesionTypes');
    }
}
