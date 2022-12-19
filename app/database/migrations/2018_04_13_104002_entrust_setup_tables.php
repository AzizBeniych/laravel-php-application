<?php

class EntrustSetupTables extends \Illuminate\Database\Migrations\Migration
{
	/**
	 * Run the migrations.
	 */
	public function up()
	{
		Schema::create('user_roles', function (\Illuminate\Database\Schema\Blueprint $table) {
			$table->engine = 'InnoDB';

			$table->increments('id');
			$table->unsignedInteger('user_id');
			$table->enum('name', Role::$ALL)->default(Role::$ALL[0]);

			$table->timestamps();

			// constrains
			$table->unique(array('user_id', 'name'));
			$table->foreign('user_id')->references('id')->on('users')
				->onUpdate('cascade')->onDelete('cascade');
		});

		/* @noinspection SqlNoDataSourceInspection,SqlResolve */
		DB::statement('ALTER TABLE user_roles
MODIFY COLUMN `name` ENUM(\'' . implode("','", Role::$ALL) . '\') CHARACTER SET latin1 NOT NULL DEFAULT \'' . Role::$ALL[0] . '\',
MODIFY COLUMN updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;');
	}

	/**
	 * Reverse the migrations.
	 */
	public function down()
	{
		Schema::dropIfExists('user_roles');
	}
}
