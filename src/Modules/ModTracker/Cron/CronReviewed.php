<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * @project FreeCRM
 * @author bmankowski@gmail.com
 * @copyright (c) FreeCRM
 * @license FreeCRM Public License 1.0
 */

declare(strict_types=1);

namespace App\Modules\ModTracker\Cron;

/**
 * @internal Ported from legacy ModTracker/cron/ReviewChanges.php
 */
final class CronReviewed
{
	public const MAX_RECORDS = 200;

	private $limit;
	private $displayed;
	private $counter = 0;
	private $done = [];
	private $valueMap = [];
	private $recordList = [];
	private $end = false;

	public function __construct()
	{
		$this->limit = \App\Core\AppConfig::module('ModTracker', 'REVIEWED_SCHEDULE_LIMIT');
		$this->displayed = \App\Modules\ModTracker\Models\Record::DISPLAYED;
	}

	public function init($row): void
	{
		if (!is_array($row)) {
			$row = [$row];
		}
		foreach ($row as $key => $value) {
			if ($key === 'data') {
				$value = \App\Utils\Json::decode($row['data']);
				$this->init($value);
			}
			$this->valueMap[$key] = $value;
		}
	}

	public function clearData(): void
	{
		$this->done = [];
		$this->valueMap = [];
		$this->recordList = [];
	}

	private function get($key)
	{
		return $this->valueMap[$key];
	}

	private function getRecords()
	{
		$data = $this->get('data');
		if ('all' === $this->get('selected_ids')) {
			$data['module'] = \App\Utils\ModuleUtils::getModuleName($this->get('tabid'));
			$request = new \App\Http\Vtiger_Request($data, $data);
			$this->recordList = \App\Modules\Base\Actions\Mass::getRecordsListFromRequest($request);
		} else {
			$this->recordList = $this->get('selected_ids');
		}
		return $this->recordList;
	}

	public function reviewChanges(): void
	{
		$db = \App\Db\Db::getInstance();
		$recordsList = $this->getRecords();
		if (!empty($recordsList)) {
			foreach ($recordsList as $crmId) {
				if ($this->counter === $this->limit) {
					$this->end = true;
					break;
				}
				$query = (new \App\Db\Query())
					->select('last_reviewed_users as u, id, changedon')
					->from('vtiger_modtracker_basic')
					->where(['crmid' => $crmId])
					->andWhere(['<>', 'status', $this->displayed])
					->orderBy(['changedon' => SORT_DESC, 'id' => SORT_DESC]);
				$dataReader = $query->createCommand($db)->query();
				while ($row = $dataReader->read()) {
					$userId = $this->get('userid');
					if (strpos($row['u'], "#$userId#") !== false) {
						break;
					} elseif (strtotime($row['changedon']) >= strtotime($this->get('changedon'))) {
						$changed = $this->setReviewed($row['id'], $row['u']);
						if ($changed) {
							\App\Modules\ModTracker\Models\Record::unsetReviewed($crmId, $userId, $row['id']);
						}
						break;
					}
				}
				$this->counter++;
				$this->done[] = $crmId;
			}
			$this->finish();
		}
	}

	private function setReviewed($id, $users)
	{
		$db = \App\Db\Db::getInstance();
		$lastReviewedUsers = explode('#', $users);
		$lastReviewedUsers[] = $this->get('userid');
		return $db->createCommand()->update(
				'vtiger_modtracker_basic', ['last_reviewed_users' => '#' . implode('#', array_filter($lastReviewedUsers)) . '#'], ['id' => $id]
			)->execute();
	}

	private function finish(): void
	{
		$db = \App\Db\Db::getInstance();
		$db->createCommand()->delete('u_#__reviewed_queue', ['=', 'id', $this->get('id')])->execute();
		if (count($this->done) < count($this->recordList)) {
			$records = array_diff($this->recordList, $this->done);
			$this->addPartToDBRecursive($records);
		}
	}

	private function addPartToDBRecursive($records): void
	{
		$db = \App\Db\Db::getInstance();
		$list = array_splice($records, 0, self::MAX_RECORDS);
		$data = \App\Utils\Json::encode(['selected_ids' => $list]);
		$id = (new \App\Db\Query())
				->from('u_#__reviewed_queue')
				->max('id') + 1;
		$db->createCommand()->insert('u_#__reviewed_queue', [
			'id' => $id,
			'userid' => $this->get('userid'),
			'tabid' => $this->get('tabid'),
			'data' => $data,
			'time' => $this->get('time')
		])->execute();
		if (!empty($records)) {
			$this->addPartToDBRecursive($records);
		}
	}

	public function isEnd(): bool
	{
		return $this->end;
	}
}
