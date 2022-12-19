<?php

class CreateFailedJobsTable extends \Illuminate\Database\Migrations\Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('failed_jobs', function (\Illuminate\Database\Schema\Blueprint $table) {
			$table->engine = 'InnoDB';

			$table->increments('id');
			$table->string('connection', 100);
			$table->string('queue', 255)->nullable();
			$table->longText('payload')->nullable();

			$table->timestamp('failed_at')->default(DB::raw('CURRENT_TIMESTAMP'));
		});

		/* @noinspection SqlNoDataSourceInspection,SqlResolve */
		DB::statement('ALTER TABLE failed_jobs
MODIFY COLUMN `connection` VARCHAR(100) CHARACTER SET latin1 NOT NULL,
MODIFY COLUMN queue VARCHAR(255) CHARACTER SET latin1 NULL;');
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::dropIfExists('failed_jobs');
	}
}
