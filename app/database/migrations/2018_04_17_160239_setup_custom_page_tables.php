<?php

use Illuminate\Database\Schema\Blueprint;

class SetupCustomPageTables extends \Illuminate\Database\Migrations\Migration
{
	/**
	 * Run the migrations.
	 */
	public function up()
	{
		// Magazines
		$this->createMagazineTable();

		// Templates
		$this->createCustomPageTable();

		// Theme Layouts
		$this->createThemeLayoutTable();

		// User-Templates association
		$this->createUserCustomPageTable();

		// Theme-Layout Form elements
		$this->createLayoutElementTable();

		// Theme-Layout Form values
		$this->createUserLayoutElementTable();

		// Theme-Layout elements Many-to-Many mapping
		$this->createThemeLayoutElementTable();
	}

	private function createMagazineTable()
	{
		Schema::create('magazines', function (Blueprint $table) {
			$table->engine = 'InnoDB';

			$table->increments('id');

			$table->string('name', 25);
			$table->string('display_name', 255)->nullable();
			$table->string('description', 255)->nullable();
			$table->string('preview_image', 255)->nullable();

			$table->tinyInteger('order')->default(0);
			$table->string('specific_agencies', 255)->nullable();
			$table->string('specific_roles', 100)->nullable();

			$table->timestamps();
			$table->unsignedInteger('deleted_at')->default(0);

			// constrains
			$table->unique(array('name', 'deleted_at'));
		});

		/* @noinspection SqlNoDataSourceInspection,SqlResolve */
		DB::statement('ALTER TABLE magazines
MODIFY COLUMN `name` VARCHAR(25) CHARACTER SET latin1 NOT NULL,
MODIFY COLUMN preview_image VARCHAR(255) CHARACTER SET latin1 NULL,
MODIFY COLUMN specific_agencies VARCHAR(255) CHARACTER SET latin1 NULL,
MODIFY COLUMN specific_roles VARCHAR(100) CHARACTER SET latin1 NULL,
MODIFY COLUMN updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;');

		// Soft-delete triggers
		DB::unprepared('CREATE TRIGGER magazines_soft_delete_trigger AFTER UPDATE ON magazines FOR EACH ROW
BEGIN
	IF NEW.deleted_at != OLD.deleted_at THEN
		UPDATE custom_pages SET deleted_at=NEW.deleted_at, updated_at=updated_at WHERE page_id=NEW.id AND deleted_at=OLD.deleted_at;
	END IF;
END');
	}

	private function createCustomPageTable()
	{
		Schema::create('custom_pages', function (Blueprint $table) {
			$table->engine = 'InnoDB';

			$table->increments('id');
			$table->unsignedInteger('magazine_id');

			$table->string('name', 25);
			$table->string('display_name', 255)->nullable();
			$table->string('description', 255)->nullable();
			$table->string('preview_image', 255)->nullable();

			$table->boolean('pre_designed')->default(true);
			$table->tinyInteger('order')->default(0);
			$table->string('specific_roles', 100)->nullable();

			$table->timestamps();
			$table->unsignedInteger('deleted_at')->default(0);

			// constrains
			$table->unique(array('name', 'deleted_at'));
			$table->foreign('magazine_id')->references('id')->on('magazines')
				->onUpdate('cascade')->onDelete('cascade');
		});

		/* @noinspection SqlNoDataSourceInspection,SqlResolve */
		DB::statement('ALTER TABLE custom_pages
MODIFY COLUMN `name` VARCHAR(25) CHARACTER SET latin1 NOT NULL,
MODIFY COLUMN preview_image VARCHAR(255) CHARACTER SET latin1 NULL,
MODIFY COLUMN specific_roles VARCHAR(100) CHARACTER SET latin1 NULL,
MODIFY COLUMN updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;');

		// Soft-delete triggers
		DB::unprepared('CREATE TRIGGER custom_pages_soft_delete_trigger AFTER UPDATE ON custom_pages FOR EACH ROW
BEGIN
	IF NEW.deleted_at != OLD.deleted_at THEN
		UPDATE theme_layouts SET deleted_at=NEW.deleted_at, updated_at=updated_at WHERE page_id=NEW.id AND deleted_at=OLD.deleted_at;
	END IF;
END');
	}

	private function createThemeLayoutTable()
	{
		Schema::create('theme_layouts', function (Blueprint $table) {
			$table->engine = 'InnoDB';

			$table->increments('id');
			$table->unsignedInteger('page_id');

			$table->string('name', 25);
			$table->string('display_name', 255)->nullable();
			$table->string('description', 255)->nullable();
			$table->string('category', 255)->nullable();
			//
			$table->string('preview_image', 255)->nullable();
			$table->string('pdf_file', 255);//->default('pdf/templates/blank.pdf')
			$table->string('generator', 100)->nullable();

			$table->tinyInteger('order')->default(0);
			$table->string('specific_roles', 100)->nullable();

			// form-builder
			$table->boolean('is_tabbed')->default(false);
			$table->string('instructions', 2000)->nullable();
			$table->string('override_fields', 4000)->nullable();// json

			// soft-deleting
			$table->timestamps();
			$table->unsignedInteger('deleted_at')->default(0);

			// constrains
			$table->unique(array('name', 'deleted_at'));
			$table->foreign('page_id')->references('id')->on('custom_pages')
				->onUpdate('cascade')->onDelete('cascade');
		});

		/* @noinspection SqlNoDataSourceInspection,SqlResolve */
		DB::statement('ALTER TABLE theme_layouts
MODIFY COLUMN `name` VARCHAR(25) CHARACTER SET latin1 NOT NULL,
MODIFY COLUMN preview_image VARCHAR(255) CHARACTER SET latin1 NULL,
MODIFY COLUMN pdf_file VARCHAR(255) CHARACTER SET latin1 NULL,
MODIFY COLUMN specific_roles VARCHAR(100) CHARACTER SET latin1 NULL,
MODIFY COLUMN updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;');
	}

	private function createUserCustomPageTable()
	{
		Schema::create('user_custom_pages', function (Blueprint $table) {
			$table->engine = 'InnoDB';

			$table->increments('id');
			$table->unsignedInteger('user_id');
			$table->unsignedInteger('page_id');
			$table->unsignedInteger('theme_id')->nullable();

			$table->timestamps();

			// constrains
			$table->unique(array('user_id', 'page_id'));
			$table->foreign('user_id')->references('id')->on('users')
				->onUpdate('cascade')->onDelete('cascade');
			$table->foreign('page_id')->references('id')->on('custom_pages')
				->onUpdate('cascade')->onDelete('cascade');
			//
			$table->foreign('theme_id')->references('id')->on('theme_layouts')
				->onUpdate('cascade')->onDelete('set null');
		});

		/* @noinspection SqlNoDataSourceInspection,SqlResolve */
		DB::statement('ALTER TABLE user_custom_pages
MODIFY COLUMN updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;');
	}

	private function createLayoutElementTable()
	{
		$INPUT_TYPES = array_keys(LayoutElement::$INPUT_TYPES);

		Schema::create('layout_elements', function (Blueprint $table) use ($INPUT_TYPES) {
			$table->engine = 'InnoDB';

			$table->increments('id');
			//$table->unsignedInteger('layout_id');

			$table->string('name', 50);
			$table->enum('input_type', $INPUT_TYPES)->default($INPUT_TYPES[0]);
			$table->string('caption', 255)->nullable(); // placeholder
			$table->string('description', 4000)->nullable(); // setting-name for option values -or- checkbox values=>labels

			$table->string('tab', 100)->nullable();
			$table->string('tab_header', 255)->nullable();
			$table->string('category', 255)->nullable();

			$table->string('attributes', 255)->nullable(); // json {multiple:"multiple",rows:"3"}
			$table->string('help_text', 4000)->nullable();
			//
			$table->string('label', 255)->nullable(); // left-label
			$table->tinyInteger('label_width')->nullable();

			$table->string('css_class', 100)->nullable();// TODO: it's never used
			$table->tinyInteger('col_width')->nullable();
			$table->string('wrapper_css', 100)->nullable();
			$table->boolean('is_inline')->default(false);

			$table->string('validation', 255)->nullable(); // json?
			$table->enum('error_placement', LayoutElement::$ERROR_PLACEMENTS)->default(LayoutElement::$ERROR_PLACEMENTS[0]);
			//
			$table->string('profile_field', 50)->nullable();

			// soft-deleting
			$table->timestamps();
			$table->unsignedInteger('deleted_at')->default(0);

			// constrains
			$table->unique(array('name', 'deleted_at'));
		});

		/* @noinspection SqlNoDataSourceInspection,SqlResolve */
		DB::statement('ALTER TABLE layout_elements
MODIFY COLUMN `name` VARCHAR(50) CHARACTER SET latin1 NOT NULL,
MODIFY COLUMN input_type ENUM(\'' . implode("','", $INPUT_TYPES) . '\') CHARACTER SET latin1 NOT NULL DEFAULT \'' . $INPUT_TYPES[0] . '\',
MODIFY COLUMN css_class VARCHAR(100) CHARACTER SET latin1 NULL,
MODIFY COLUMN wrapper_css VARCHAR(100) CHARACTER SET latin1 NULL,
MODIFY COLUMN `validation` VARCHAR(255) CHARACTER SET latin1 NULL,
MODIFY COLUMN error_placement ENUM(\'' . implode("','", LayoutElement::$ERROR_PLACEMENTS) . '\') CHARACTER SET latin1 NOT NULL DEFAULT \'' . LayoutElement::$ERROR_PLACEMENTS[0] . '\',
MODIFY COLUMN profile_field VARCHAR(50) CHARACTER SET latin1 NULL,
MODIFY COLUMN updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;');
	}

	private function createThemeLayoutElementTable()
	{
		Schema::create('theme_layout_elements', function (Blueprint $table) {
			$table->engine = 'InnoDB';

			$table->increments('id');
			$table->unsignedInteger('layout_id');
			$table->unsignedInteger('element_id');
			$table->tinyInteger('order')->default(0);

			// constrains
			$table->unique(array('layout_id', 'element_id'));
			$table->foreign('layout_id')->references('id')->on('theme_layouts')
				->onUpdate('cascade')->onDelete('cascade');
			$table->foreign('element_id')->references('id')->on('layout_elements')
				->onUpdate('cascade')->onDelete('cascade');
		});
	}

	private function createUserLayoutElementTable()
	{
		Schema::create('user_layout_elements', function (Blueprint $table) {
			$table->engine = 'InnoDB';

			$table->increments('id');
			$table->unsignedInteger('user_id');
			$table->unsignedInteger('element_id');
			$table->text('value')->nullable();

			$table->timestamps();

			// constrains
			$table->unique(array('user_id', 'element_id'));
			$table->foreign('user_id')->references('id')->on('users')
				->onUpdate('cascade')->onDelete('cascade');
			$table->foreign('element_id')->references('id')->on('layout_elements')
				->onUpdate('cascade')->onDelete('cascade');
		});

		/* @noinspection SqlNoDataSourceInspection,SqlResolve */
		DB::statement('ALTER TABLE user_layout_elements
MODIFY COLUMN updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;');
	}

	/**
	 * Reverse the migrations.
	 */
	public function down()
	{
		DB::unprepared('DROP TRIGGER IF EXISTS magazines_soft_delete_trigger');
		DB::unprepared('DROP TRIGGER IF EXISTS custom_pages_soft_delete_trigger');

		Schema::dropIfExists('theme_layout_elements');
		Schema::dropIfExists('user_layout_elements');
		Schema::dropIfExists('layout_elements');
		Schema::dropIfExists('user_custom_pages');
		Schema::dropIfExists('theme_layouts');
		Schema::dropIfExists('custom_pages');
		Schema::dropIfExists('magazines');
	}
}
