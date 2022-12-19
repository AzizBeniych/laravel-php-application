<?php

class UsersTableSeeder extends \Seeder
{
	/**
	 * {@inheritDoc}
	 */
	public function run()
	{
		DB::table(Role::TABLE)->delete();
		DB::table('agency_contacts')->delete();
		DB::table(User::TABLE)->delete();
		DB::table(Agency::TABLE)->delete();

		$this->seedAdministrators();

		$this->importAgentsCsv(__DIR__ . '/Agency_PMV_ContactInfo.csv');
		$this->seedUserIndexes();
	}

	private function importAgentsCsv($csvFile)
	{
		@ini_set('auto_detect_line_endings', true);

		if (($fh = @fopen($csvFile, 'r')) === false)
			throw new \InvalidArgumentException('Invalid CSV file: ' . $csvFile);

		$agencies = array();
		$users = array();

		$i = 1;
		try {
			while (($row = fgetcsv($fh, 1024)) !== false) {
				if (empty($header)) {
					$header = static::parseCsvHeader($row);
				} else {
					if (count($row) !== count($header))
						throw new \InvalidArgumentException('Invalid fields count');

					$row = array_combine($header, $row);
					list($agency, $agents) = static::parseCsvRowData($row);
					unset($row);

					$agencies[] = $agency;
					unset($agency);

					$users = static::mergeAgentRecords($users, $agents);
					unset($agents);
				}

				$i++;
			}
		} catch (\Exception $e) {
			@fclose($fh);
			throw new \UnexpectedValueException("[CSV row: $i] " . $e->getMessage(), $e->getCode(), $e);
		}
		@fclose($fh);

		$users = array_values($users);
		list($users, $contacts, $roles) = static::prepareAgencyContacts($users);

		$this->importAgencyContacts($agencies, $users, $contacts, $roles);
	}

	private static function mergeAgentRecords(array $users, array $agents)
	{
		foreach ($agents as $k => $u) {
			if (isset($users[$k])) {
				// DEBUG
				Log::warning('Merge multi-agency duplicated agent: ' . json_encode($u));
				$users[$k]['contacts'] = array_merge($users[$k]['contacts'], $u['contacts']);
			} else {
				$users[$k] = $u;
			}
		}

		return $users;
	}

	private static function prepareAgencyContacts(array $users)
	{
		$contacts = array();
		$roles = array();

		foreach ($users as $i => &$u) {
			$id = $i + 10;
			$u['id'] = $id;

			if (isset($u['password'])) {
				$u['password'] = \Hash::make($u['password']);
				$u['confirmed'] = true;
			} else {
				$u['password'] = '';
				$u['confirmed'] = false;
			}

			foreach ($u['contacts'] as $r) {
				$contacts[] = $r + array('user_id' => $id, User::CREATED_AT => DatabaseSeeder::INIT_TS);
			}
			unset($u['contacts']);

			$roles[] = array('user_id' => $id, 'name' => Role::USER);
		}

		return array($users, $contacts, $roles);
	}

	private function importAgencyContacts(array $agencies, array $users, array $contacts, array $roles)
	{
		$agencies = DatabaseSeeder::repairTimestampRecords($agencies);
		DB::table(Agency::TABLE)->insert($agencies);
		unset($agencies);

		$users = DatabaseSeeder::repairTimestampRecords($users);
		DB::table(User::TABLE)->insert($users);
		unset($users);

		DB::table('agency_contacts')->insert($contacts);
		unset($contacts);

		$roles = DatabaseSeeder::repairTimestampRecords($roles);
		DB::table(Role::TABLE)->insert($roles);
		unset($roles);
	}

	private static function parseCsvHeader(array $row)
	{
		foreach ($row as &$s)
			$s = trim(preg_replace('/[^\pL\pN]+/u', ' ', $s));

		return $row;
	}

	private static $CSV_HEADER_MAP = array(
		'agency' => array(
			'id' => '/\bAgency\s*ID\b/i',
			'name' => '/\bAgency(?:\s*Name)?\b/i',
			'print_marketing_version' => '/\bPrint(?:\s*marketing)?\s*version\b/i',
			'leaders' => '/\bLead(?:\s*Contact)?(?:\s*s)?\b/i',
		),
		'agents' => array(
			'name' => '/\b(?:Marketing\s*)?Contact(?:\s*Name)?\s*(\d+)\b/i',
			'email' => '/\b(?:Marketing\s*)?(?:Contact\s*)?Email\s*(\d+)\b/i',
			'username' => '/\b(?:Marketing\s*)?(?:Contact\s*)?Username\s*(\d+)\b/i',
			'password' => '/\b(?:Marketing\s*)?(?:Contact\s*)?Password\s*(\d+)\b/i',
		),
	);

	private static function parseCsvRowData(array $row)
	{
		$agency = array();
		$agents = array();

		foreach ($row as $k => $v) {
			if (!isset($v) || ($v = trim($v)) === '')
				continue;

			foreach (static::$CSV_HEADER_MAP as $var => $map) {
				foreach ($map as $f => $re) {
					$result =& $$var;

					if (preg_match($re, $k, $m)
						&& ((is_array($m) && isset($m[1]) && !isset($result[$m[1] - 1][$f])) || (!isset($m[1]) && !isset($result[$f])))
					) {
						if (isset($m[1]))
							$result[$m[1] - 1][$f] = $v;
						else
							$result[$f] = $v;

						break;
					}
				}
			}
		}

		static::validateAgencyModel($agency);
		static::validateAgentModels($agents);

		$agents = static::removeDuplicateAgents($agents);
		$agents = static::prepareAgencyLeaders($agency, $agents);
		unset($agency['leaders']);

		return array($agency, $agents);
	}

	private static function validateAgencyModel(array $agency)
	{
		if (empty($agency['id']) || !is_numeric($agency['id']) || empty($agency['name'])) {
			throw new \InvalidArgumentException('Invalid agency: ' . json_encode($agency));
		}
	}

	private static function validateAgentModels(array $agents)
	{
		foreach ($agents as $i => $u) {
			if (empty($u['name'])
				|| empty($u['email']) || !CustomValidator::validateEmails(null, $u['email'])
				|| empty($u['username']) || !CustomValidator::validateUsername(null, $u['username'])
			) {
				throw new \InvalidArgumentException("Invalid agent #$i " . json_encode($u));
			}
		}
	}

	private static function removeDuplicateAgents(array $agents)
	{
		$unique = array();

		foreach ($agents as $u) {
			if (in_array($u, $unique))
				Log::info('Ignore duplicated agent: ' . json_encode($u));
			elseif (isset($unique[$u['username']]))
				Log::warning('Skip duplicated agent: ' . json_encode($u));
			else
				$unique[$u['username']] = $u;
		}

		return $unique;
	}

	private static function prepareAgencyLeaders(array $agency, array $agents)
	{
		if (isset($agency['leaders']))
			$leaders = ',' . $agency['leaders'] . ',';

		foreach ($agents as &$u) {
			$u['print_marketing_version'] = $agency['print_marketing_version'];

			$u['contacts'] = array(
				array(
					'agency_id' => $agency['id'],
					'is_lead' => (isset($leaders) && stripos($leaders, ',' . $u['name'] . ',') !== false),
				)
			);
		}

		return $agents;
	}

	private function seedUserIndexes()
	{
		DB::table(User::TABLE)->update(array(User::UPDATED_AT => DB::raw(User::UPDATED_AT)));
	}

	private function seedAdministrators()
	{
		$users = array(
			array(
				'id' => 1,
				'username' => 'admin',
				'password' => Hash::make('Admin@123!'),
				'confirmed' => true,
				//'confirmation_code' => md5(microtime() . Config::get('app.key')),
				'email' => Config::get('settings.email.admin'),
				'name' => 'System Administrator',
			),
		);

		$roles = array_map(function ($u) {
			return array('user_id' => $u['id'], 'name' => Role::ADMIN);
		}, $users);

		$users = DatabaseSeeder::repairTimestampRecords($users);
		DB::table(User::TABLE)->insert($users);
		unset($users);

		$roles = DatabaseSeeder::repairTimestampRecords($roles);
		DB::table(Role::TABLE)->insert($roles);
		unset($roles);
	}

}
