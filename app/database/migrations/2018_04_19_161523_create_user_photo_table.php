<?php

class CreateUserPhotoTable extends \Illuminate\Database\Migrations\Migration
{
	/**
	 * Run the migrations.
	 */
	public function up()
	{
		Schema::create('user_photos', function (\Illuminate\Database\Schema\Blueprint $table) {
			$table->engine = 'InnoDB';

			$table->increments('id');
			$table->unsignedInteger('user_id');
			$table->string('filename', 255);
			$table->enum('type', UserPhoto::$TYPES)->default(UserPhoto::$TYPES[0]);

			$table->string('mime_type', 128)->nullable();
			$table->boolean('is_processed')->default(false);
			$table->boolean('has_thumbnail')->default(false);
			$table->timestamp('active_at')->nullable();

			$table->timestamps();

			// constrains
			$table->unique(array('user_id', 'filename', 'type'));
			$table->foreign('user_id')->references('id')->on('users')
				->onUpdate('cascade')->onDelete('cascade');
		});

		/* @noinspection SqlNoDataSourceInspection,SqlResolve */
		DB::statement('ALTER TABLE user_photos
MODIFY COLUMN `type` ENUM(\'' . implode("','", UserPhoto::$TYPES) . '\') CHARACTER SET latin1 NOT NULL DEFAULT \'' . UserPhoto::$TYPES[0] . '\',
MODIFY COLUMN mime_type VARCHAR(128) CHARACTER SET latin1 NULL,
MODIFY COLUMN updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;');
	}

	/**
	 * Reverse the migrations.
	 */
	public function down()
	{
		Schema::dropIfExists('user_photos');
	}
}
