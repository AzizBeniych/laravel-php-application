<?php

class CreateQueueTable extends \Illuminate\Database\Migrations\Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('queues', function (\Illuminate\Database\Schema\Blueprint $table) {
			$table->engine = 'InnoDB';

			$table->bigIncrements('id');
			$table->string('queue', 255)->nullable()->default(null);
			$table->integer('status')->default(0);  // 0: OPEN, 1: WAITING, 2: STARTED, 3: FINISHED
			$table->integer('retries')->default(0);
			$table->timestamp('timestamp'); // delay
			$table->longText('payload')->nullable();

			$table->timestamps();
		});

		/* @noinspection SqlNoDataSourceInspection,SqlResolve */
		DB::statement('ALTER TABLE queues
MODIFY COLUMN queue VARCHAR(255) CHARACTER SET latin1 NULL,
MODIFY COLUMN updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;');
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::dropIfExists('queues');
	}
}
