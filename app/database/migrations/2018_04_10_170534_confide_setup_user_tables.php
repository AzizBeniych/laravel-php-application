<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;

class ConfideSetupUserTables extends \Illuminate\Database\Migrations\Migration
{
	/**
	 * Run the migrations.
	 */
	public function up()
	{
		// Companies
		$this->createAgencyTable();

		// Agents
		$this->createUserTable();

		// Agency-Agents relationship
		$this->createAgencyContactsTable();

		// For password reminder
		$this->createPasswordReminderTable();
	}

	private function createAgencyTable()
	{
		Schema::create('agencies', function (Blueprint $table) {
			$table->engine = 'InnoDB';

			$table->increments('id');
			$table->string('name', 255); //unique ? //agency_name
			$table->string('main_phone', 25)->nullable(); //agency_phone
			$table->string('toll_free_number', 25)->nullable(); //phone_toll_free
			$table->string('phone', 25)->nullable();

			$table->string('address', 255)->nullable();
			$table->string('city', 100)->nullable();//Sunnyvale, CA 94089
			$table->string('state', 50)->nullable();
			$table->string('zip', 20)->nullable();

			$table->string('fax', 25)->nullable();
			$table->string('email', 255)->nullable();
			$table->string('website', 255)->nullable(); //website_url

			$table->string('seller_of_travel', 20)->nullable(); //cst_number #
			$table->string('print_marketing_version', 20)->nullable(); // #
			$table->string('logo', 255)->nullable(); //spot_logo

			$table->timestamps();
		});

		/* @noinspection SqlNoDataSourceInspection,SqlResolve */
		DB::statement('ALTER TABLE agencies
MODIFY COLUMN main_phone VARCHAR(25) CHARACTER SET latin1 NULL,
MODIFY COLUMN toll_free_number VARCHAR(25) CHARACTER SET latin1 NULL,
MODIFY COLUMN phone VARCHAR(25) CHARACTER SET latin1 NULL,
MODIFY COLUMN state VARCHAR(50) CHARACTER SET latin1 NULL,
MODIFY COLUMN zip VARCHAR(20) CHARACTER SET latin1 NULL,
MODIFY COLUMN fax VARCHAR(25) CHARACTER SET latin1 NULL,
MODIFY COLUMN email VARCHAR(255) CHARACTER SET latin1 NULL,
MODIFY COLUMN website VARCHAR(255) CHARACTER SET latin1 NULL,
MODIFY COLUMN seller_of_travel VARCHAR(20) CHARACTER SET latin1 NULL,
MODIFY COLUMN print_marketing_version VARCHAR(20) CHARACTER SET latin1 NULL,
MODIFY COLUMN logo VARCHAR(255) CHARACTER SET latin1 NULL,
MODIFY COLUMN updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;');
	}

	private function createUserTable()
	{
		Schema::create('users', function (Blueprint $table) {
			$table->engine = 'InnoDB';

			$table->increments('id');
			$table->string('username', 50);
			$table->string('password', 100);
			$table->string('confirmation_code', 50)->nullable();
			$table->rememberToken();
			$table->boolean('confirmed')->default(false);

			// User Profile
			$table->string('name', 100)->nullable();
			$table->string('local_phone', 25)->nullable(); //phone
			$table->string('toll_free_number', 25)->nullable(); //phone_toll_free
			//
			$table->string('address', 255)->nullable();
			$table->string('city', 100)->nullable();
			$table->string('state', 50)->nullable();
			$table->string('zip', 20)->nullable();
			//
			$table->string('fax', 25)->nullable();
			$table->string('email', 255);
			$table->string('website', 255)->nullable(); //website_url

			$table->string('cst_number', 20)->nullable(); // #
			$table->string('print_marketing_version', 20)->nullable(); // #
			$table->string('photo', 255)->nullable(); //agent_bio_photo
			$table->text('bio')->nullable(); //agent_bio

			// TODO: Sign-up info

			$table->timestamps();
			$table->unsignedInteger('deleted_at')->default(0);

			// constrains
			$table->unique(array('username', 'deleted_at'));
		});

		/* @noinspection SqlNoDataSourceInspection,SqlResolve */
		DB::statement('ALTER TABLE users
MODIFY COLUMN username VARCHAR(50) CHARACTER SET latin1 NOT NULL,
MODIFY COLUMN `password` VARCHAR(100) CHARACTER SET latin1 COLLATE latin1_general_cs NOT NULL,
MODIFY COLUMN confirmation_code VARCHAR(50) CHARACTER SET latin1 NULL,
MODIFY COLUMN remember_token VARCHAR(100) CHARACTER SET latin1 COLLATE latin1_general_cs NULL,
MODIFY COLUMN local_phone VARCHAR(25) CHARACTER SET latin1 NULL,
MODIFY COLUMN toll_free_number VARCHAR(25) CHARACTER SET latin1 NULL,
MODIFY COLUMN state VARCHAR(50) CHARACTER SET latin1 NULL,
MODIFY COLUMN zip VARCHAR(20) CHARACTER SET latin1 NULL,
MODIFY COLUMN fax VARCHAR(25) CHARACTER SET latin1 NULL,
MODIFY COLUMN email VARCHAR(255) CHARACTER SET latin1 NOT NULL,
MODIFY COLUMN website VARCHAR(255) CHARACTER SET latin1 NULL,
MODIFY COLUMN cst_number VARCHAR(20) CHARACTER SET latin1 NULL,
MODIFY COLUMN print_marketing_version VARCHAR(20) CHARACTER SET latin1 NULL,
MODIFY COLUMN photo VARCHAR(255) CHARACTER SET latin1 NULL,
MODIFY COLUMN updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;');
	}

	private function createAgencyContactsTable()
	{
		Schema::create('agency_contacts', function (Blueprint $table) {
			$table->engine = 'InnoDB';

			$table->increments('id');
			$table->unsignedInteger('agency_id');
			$table->unsignedInteger('user_id');
			$table->boolean('is_lead')->default(false);

			$table->timestamp(Model::CREATED_AT)->default(DB::raw('CURRENT_TIMESTAMP'));

			// constrains
			$table->unique(array('agency_id', 'user_id'));

			$table->foreign('agency_id')->references('id')->on('agencies')
				->onUpdate('cascade')->onDelete('cascade');
			$table->foreign('user_id')->references('id')->on('users')
				->onUpdate('cascade')->onDelete('cascade');
		});
	}

	private function createPasswordReminderTable()
	{
		Schema::create('password_reminders', function (Blueprint $table) {
			$table->engine = 'InnoDB';

			$table->increments('id');
			$table->string('email', 255); //unique ?
			$table->string('token', 50)->unique();

			$table->timestamp(Model::CREATED_AT)->default(DB::raw('CURRENT_TIMESTAMP'));
		});

		/* @noinspection SqlNoDataSourceInspection,SqlResolve */
		DB::statement('ALTER TABLE password_reminders
MODIFY COLUMN email VARCHAR(255) CHARACTER SET latin1 NOT NULL,
MODIFY COLUMN token VARCHAR(50) CHARACTER SET latin1 NOT NULL;');
	}

	/**
	 * Reverse the migrations.
	 */
	public function down()
	{
		Schema::dropIfExists('password_reminders');
		Schema::dropIfExists('agency_contacts');
		Schema::dropIfExists('users');
		Schema::dropIfExists('agencies');
	}
}
