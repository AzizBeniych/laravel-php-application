<?php

class CreateUserFulltextTable extends \Illuminate\Database\Migrations\Migration
{
	/**
	 * Run the migrations.
	 */
	public function up()
	{
		// NOTE: ft_min_word_len=4
		Schema::create('users_index', function (\Illuminate\Database\Schema\Blueprint $table) {
			$table->engine = 'MyISAM';

			$table->unsignedInteger('iid')->primary();

			$table->string('agencies', 2000)->nullable();
			$table->string('username', 50);
			$table->string('roles', 20)->nullable();

			// User Profile
			$table->string('name', 100)->nullable();
			$table->string('local_phone', 25)->nullable();
			$table->string('toll_free_number', 25)->nullable();
			//
			$table->string('address', 255)->nullable();
			$table->string('city', 100)->nullable();
			$table->string('state', 50)->nullable();
			$table->string('zip', 20)->nullable();
			//
			$table->string('fax', 25)->nullable();
			$table->string('email', 255);
			$table->string('website', 255)->nullable();

			$table->string('cst_number', 20)->nullable();
			$table->string('print_marketing_version', 20)->nullable();
			//$table->string('photo', 255)->nullable();
			$table->text('bio')->nullable();
		});

		// Full-Text index
		/* @noinspection SqlNoDataSourceInspection,SqlResolve */
		DB::statement('ALTER TABLE users_index
ADD FULLTEXT INDEX users_index_ft (agencies, username, roles, `name`, local_phone, toll_free_number, address, city, state, zip, fax, email, website, cst_number, print_marketing_version, bio)');

		// Triggers
		DB::unprepared('CREATE TRIGGER users_index_insert_trigger AFTER INSERT ON users FOR EACH ROW
BEGIN
INSERT INTO users_index VALUES (
	NEW.id,
	NULL,
	NEW.username,
	NULL,
	NEW.`name`,
	NEW.local_phone,
	NEW.toll_free_number,
	NEW.address,
	NEW.city,
	NEW.state,
	NEW.zip,
	NEW.fax,
	NEW.email,
	NEW.website,
	NEW.cst_number,
	NEW.print_marketing_version,
	NEW.bio);
END');

		// Soft-delete triggers
		DB::unprepared('CREATE TRIGGER users_index_update_trigger AFTER UPDATE ON users FOR EACH ROW
BEGIN
UPDATE users_index SET
	iid = NEW.id,
	agencies = (SELECT GROUP_CONCAT(a.`name` SEPARATOR \'\n\') FROM agency_contacts r INNER JOIN agencies a ON r.agency_id=a.id WHERE r.user_id=NEW.id),
	username = NEW.username,
	roles = (SELECT GROUP_CONCAT(r.`name` SEPARATOR \' \') FROM user_roles r WHERE r.user_id=NEW.id),
	`name` = NEW.`name`,
	local_phone = NEW.local_phone,
	toll_free_number = NEW.toll_free_number,
	address = NEW.address,
	city = NEW.city,
	state = NEW.state,
	zip = NEW.zip,
	fax = NEW.fax,
	email = NEW.email,
	website = NEW.website,
	cst_number = NEW.cst_number,
	print_marketing_version = NEW.print_marketing_version,
	bio = NEW.bio
WHERE iid=OLD.id;
END');

		DB::unprepared('CREATE TRIGGER users_index_delete_trigger AFTER DELETE ON users FOR EACH ROW
BEGIN
	DELETE FROM users_index WHERE iid=OLD.id;
END');
	}

	/**
	 * Reverse the migrations.
	 */
	public function down()
	{
		DB::unprepared('DROP TRIGGER IF EXISTS users_index_delete_trigger');
		DB::unprepared('DROP TRIGGER IF EXISTS users_index_update_trigger');
		DB::unprepared('DROP TRIGGER IF EXISTS users_index_insert_trigger');

		Schema::dropIfExists('users_index');
	}
}
