<?php

use Illuminate\Database\Eloquent\Model;

class DatabaseSeeder extends \Seeder
{

	/**
	 * {@inheritDoc}
	 */
	public function run()
	{
		\Eloquent::unguard();

		$this->call('UsersTableSeeder');
		$this->call('MagazineTableSeeder');
	}

	const INIT_TS = '2018-03-02 16:26:51';

	/**
	 * @param  Model|array $rec
	 * @return Model|array
	 */
	public static function repairTimestampRecord($rec)
	{
		if ($rec instanceof Model) {
			$dt = new \DateTime(static::INIT_TS);

			if (!$rec->exists) {
				$rec->setCreatedAt($dt);
			} else {
				$rec->setUpdatedAt(null);
				$rec->syncOriginalAttribute(Model::UPDATED_AT);
			}
			$rec->setUpdatedAt($dt);

		} elseif (is_array($rec)) {
			$rec[Model::CREATED_AT] = static::INIT_TS;
			$rec[Model::UPDATED_AT] = static::INIT_TS;
		}

		return $rec;
	}

	public static function repairTimestampRecords(array $recs, $callback = null)
	{
		foreach ($recs as &$rec) {
			if (is_callable($callback))
				$rec = $callback($rec);

			if (!isset($header)) {
				$header = array_keys($rec);
			} else {
				$diff = array_diff($header, array_keys($rec));
				if (!empty($diff))
					throw new \UnexpectedValueException('Invalid record keys: ' . print_r($rec, true));
			}

			$rec = static::repairTimestampRecord($rec);
		}

		return $recs;
	}

}
