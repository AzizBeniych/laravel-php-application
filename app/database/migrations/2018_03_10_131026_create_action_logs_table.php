<?php

class CreateActionLogsTable extends \Illuminate\Database\Migrations\Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('action_logs', function (\Illuminate\Database\Schema\Blueprint $table) {
			$table->engine = 'InnoDB';

			$table->bigIncrements('id');
			$table->enum('action', ActionLog::actions())->default(ActionLog::actions(0));
			$table->enum('object_type', ActionLog::objectTypes())->default(ActionLog::objectTypes(0));
			$table->unsignedInteger('object_id');
			$table->string('description', 255)->nullable();

			$table->unsignedInteger('created_by');
			$table->timestamps();

			// constrains
			$table->unique(array('action', 'object_type', 'object_id', 'created_by', 'created_at'), 'action_logs_unique');
		});

		/* @noinspection SqlNoDataSourceInspection,SqlResolve */
		DB::statement('ALTER TABLE action_logs
MODIFY COLUMN `action` ENUM(\'' . implode("','", ActionLog::actions()) . '\') CHARACTER SET latin1 NOT NULL DEFAULT \'' . ActionLog::actions(0) . '\',
MODIFY COLUMN object_type ENUM(\'' . implode("','", ActionLog::objectTypes()) . '\') CHARACTER SET latin1 NOT NULL DEFAULT \'' . ActionLog::objectTypes(0) . '\',
MODIFY COLUMN updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;');
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::dropIfExists('action_logs');
	}
}
