<?php

class MagazineTableSeeder extends \Seeder
{
	/**
	 * {@inheritDoc}
	 */
	public function run()
	{
		DB::table(Magazine::TABLE)->delete();

		$i = 0;
		$recs = array(
			array(
				'id' => ++$i,
				'name' => 'm1',
				'display_name' => 'The Travel Magazine',
				'description' => 'Travel Awaken your Senses',
				'preview_image' => Magazine::BASE_PATH . '/2018/m1_awaken-your-senses.jpg',
			),
			array(
				'id' => ++$i,
				'name' => 'm2',
				'display_name' => 'Travel Redefined',
				'description' => 'Travel Redefined',
				'preview_image' => Magazine::BASE_PATH . '/2018/m2_redefined.jpg',
			),
			array(
				'id' => ++$i,
				'name' => 'm3',
				'display_name' => 'Ultimate Experiences',
				'description' => 'Ultimate Experiences',
				'preview_image' => Magazine::BASE_PATH . '/2018/m3_ultimate-experiences.jpg',
			),
		);

		$order = 0;
		$recs = DatabaseSeeder::repairTimestampRecords($recs, function ($rec) use (&$order) {
			$order += 2;
			$rec['order'] = $order;
			return $rec;
		});

		DB::table(Magazine::TABLE)->insert($recs);
	}

}
