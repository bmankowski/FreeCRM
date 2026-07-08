<?php

namespace App\Modules\Base\Widgets;

class HistoryRelation extends \App\Modules\Base\Widgets\Basic
{

	public static $colors = [
		'ModComments' => 'bgBlue',
		'MailReceived' => 'bgDanger',
		'MailSent' => 'bgGreen',
		'Calendar' => 'bgOrange',
	];

	static public function getActions()
	{
		$modules = ['ModComments', 'Mail', 'Calendar'];
		foreach ($modules as $key => $module) {
			if (!\App\Security\Privilege::isPermitted($module)) {
				unset($modules[$key]);
			}
		}
		return $modules;
	}

	public function getUrl()
	{
		$url = 'module=' . $this->Module . '&view=Detail&record=' . $this->Record . '&mode=showRecentRelation&page=1&limit=' . $this->Data['limit'];
		foreach (self::getActions() as $type) {
			$url .= '&type[]=' . $type;
		}
		return $url;
	}

	public function getWidget()
	{
		$this->Config['tpl'] = 'HistoryRelation.tpl';
		$this->Config['url'] = $this->getUrl();
		$this->Config['data']['historyActions'] = self::getActions();
		return $this->Config;
	}

	public function getConfigTplName()
	{
		return 'HistoryRelationConfig';
	}

	public static function getHistory(\App\Http\Vtiger_Request $request, \App\Modules\Base\Models\Paging $pagingModel)
	{
		$recordId = $request->get('record');
		$type = $request->get('type');
		if (empty($type)) {
			return [];
		}

		$query = static::getQuery($recordId, $request->getModule(), $type);
		if (empty($query)) {
			return [];
		}
		$startIndex = $pagingModel->getStartIndex();
		$pageLimit = $pagingModel->getPageLimit();

		$query->limit($pageLimit)->offset($startIndex);
		$history = [];
		$groups = \App\Modules\Settings\Groups\Models\Record::getAll();
		$groupIds = array_keys($groups);
		$dataReader = $query->createCommand()->query();
		while ($row = $dataReader->read()) {
			if (in_array($row['user'], $groupIds)) {
				$row['isGroup'] = true;
				$row['userModel'] = $groups[$row['user']];
			} else {
				$row['isGroup'] = false;
				$row['userModel'] = \App\Modules\Users\Models\Record::getInstanceById((int) $row['user'], 'Users');
			}
			$row['class'] = self::$colors[$row['type']] ?? 'bgBlue';
			if (strpos((string) $row['type'], 'Mail') === 0) {
				$row['type'] = 'Mail';
				$row['url'] = 'index.php?module=Mail&view=Detail&record=' . (int) $row['id'];
			} else {
				$row['url'] = \App\Modules\Base\Models\Module::getInstance($row['type'])->getDetailViewUrl($row['id']);
			}
			$body = trim(\App\Security\Purifier::purify($row['body']));
			if (!$request->getBoolean('isFullscreen')) {
				$body = \vtlib\Functions::textLength($body, 100);
			} else {
				$body = str_replace(['<p></p>', '<p class="MsoNormal">'], ["\r\n", "\r\n"], \App\Utils\ListViewUtils::decodeHtml(\App\Security\Purifier::purify($body)));
				$body = nl2br(\vtlib\Functions::textLength($body, 500), false);
			}
			$row['body'] = $body;
			$history[] = $row;
		}
		return $history;
	}

	public static function getQuery($recordId, $moduleName, $type)
	{
		$queries = [];
		$field = \App\Core\ModuleHierarchy::getMappingRelatedField($moduleName);
		$db = \App\Db\Db::getInstance();
		if (in_array('Calendar', $type)) {
			$query = (new \App\Db\Query())
				->select([
					'body' => new \yii\db\Expression($db->quoteValue('')),
					'attachments_exist' => new \yii\db\Expression($db->quoteValue('')),
					'type' => new \yii\db\Expression($db->quoteValue('Calendar')),
					'id' => 'vtiger_crmentity.crmid',
					'content' => 'a.subject',
					'user' => 'vtiger_crmentity.smownerid',
					'time' => new \yii\db\Expression('CONCAT(a.date_start, ' . $db->quoteValue(' ') . ', a.time_start)')
				])
				->from('vtiger_activity a')
				->innerJoin('vtiger_crmentity', 'vtiger_crmentity.crmid = a.activityid')
				->where(['vtiger_crmentity.deleted' => 0])
				->andWhere(['=', 'a.' . $field, $recordId]);
			\App\Security\PrivilegeQuery::getConditions($query, 'Calendar', false, $recordId);
			$queries[] = $query;
		}
		if (in_array('ModComments', $type)) {
			$query = (new \App\Db\Query())
				->select([
					'body' => new \yii\db\Expression($db->quoteValue('')),
					'attachments_exist' => new \yii\db\Expression($db->quoteValue('')),
					'type' => new \yii\db\Expression($db->quoteValue('ModComments')),
					'id' => 'm.modcommentsid',
					'content' => 'm.commentcontent',
					'user' => 'vtiger_crmentity.smownerid',
					'time' => 'vtiger_crmentity.createdtime'
				])
				->from('vtiger_modcomments m')
				->innerJoin('vtiger_crmentity', 'vtiger_crmentity.crmid = m.modcommentsid')
				->where(['vtiger_crmentity.deleted' => 0])
				->andWhere(['=', 'related_to', $recordId]);
			\App\Security\PrivilegeQuery::getConditions($query, 'ModComments', false, $recordId);
			$queries[] = $query;
		}
		if (in_array('Mail', $type)) {
			$query = (new \App\Db\Query())
				->select([
					'body' => 'm.body_text',
					'attachments_exist' => new \yii\db\Expression('IF(m.has_attachments, 1, 0)'),
					'type' => new \yii\db\Expression("IF(m.direction = 'out', 'MailSent', 'MailReceived')"),
					'id' => 'm.id',
					'content' => 'm.subject',
					'user' => 'm.sender_user_id',
					'time' => 'm.date_sent',
				])
				->from(['m' => 'u_yf_mail_messages'])
				->innerJoin(['l' => 'u_yf_mail_record_links'], 'l.message_id = m.id')
				->where(['l.crm_module' => $moduleName, 'l.crm_record_id' => $recordId]);
			$user = \App\User\CurrentUser::get();
			$userId = (int) ($user?->getId() ?? 0);
			if ($user === null || !$user->isAdminUser()) {
				$query->leftJoin(['a' => 'u_yf_mail_accounts'], 'a.id = m.account_id')
					->andWhere([
						'or',
						['m.smtp_id' => null, 'a.kind' => 'group'],
						['not', ['m.smtp_id' => null]],
						['and', ['a.kind' => 'personal'], ['a.owner_user_id' => $userId]],
						['and', ['m.direction' => 'out'], ['m.sender_user_id' => $userId]],
					]);
			}
			$queries[] = $query;
		}
		if (count($queries) === 0) {
			return false;
		}
		if (count($queries) === 1) {
			$sql = reset($queries);
		} else {
			$subQuery = reset($queries);
			$index = 0;
			foreach ($queries as $query) {
				if ($index !== 0) {
					$subQuery->union($query, true);
				}
				$index++;
			}
			$sql = (new \App\Db\Query)->from(['records' => $subQuery]);
		}
		return $sql->orderBy(['time' => SORT_DESC]);
	}
}
