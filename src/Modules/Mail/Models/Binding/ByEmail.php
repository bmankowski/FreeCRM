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

namespace App\Modules\Mail\Models\Binding;

class ByEmail
{
	private static array $rules = [
		['module' => 'Candidates', 'fields' => ['email_private', 'email_business', 'newsletter_email', 'referred_by_email']],
		['module' => 'Leads', 'fields' => ['email', 'secondaryemail']],
		['module' => 'Accounts', 'fields' => ['email1', 'email2']],
		['module' => 'Contacts', 'fields' => ['email', 'secondaryemail']],
		['module' => 'HelpDesk', 'fields' => ['email', 'contact_email']],
		['module' => 'SSalesProcesses', 'fields' => ['email1']],
	];

	public static function bind(int $messageId, array $addresses): array
	{
		$linked = [];
		$normalized = array_unique(array_filter(array_map([self::class, 'normalize'], $addresses)));
		if ($normalized === []) {
			return $linked;
		}

		foreach (self::$rules as $rule) {
			$module = $rule['module'];
			$entity = \App\Core\CRMEntity::getInstance($module);
			if (!$entity || !property_exists($entity, 'table_index')) {
				continue;
			}
			$moduleModel = \App\Modules\Base\Models\Module::getInstance($module);
			if (!$moduleModel) {
				continue;
			}
			$indexColumn = $entity->table_index;
			foreach ($rule['fields'] as $field) {
				$fieldModel = $moduleModel->getField($field);
				if (!$fieldModel || !$fieldModel->isActiveField()) {
					continue;
				}
				$table = $fieldModel->getTableName();
				$column = $fieldModel->getColumnName();
				if (!$table || !$column) {
					continue;
				}
				$rows = (new \App\Db\Query())
					->select(['crmid' => 'ce.crmid'])
					->from(['ce' => 'vtiger_crmentity'])
					->innerJoin(['m' => $table], "m.{$indexColumn} = ce.crmid")
					->where(['ce.deleted' => 0, 'ce.setype' => $module])
					->andWhere(['in', "m.$column", $normalized])
					->all();
				foreach ($rows as $row) {
					if (Engine::link($messageId, $module, (int) $row['crmid'], 'auto', $field)) {
						$linked[] = ['module' => $module, 'id' => (int) $row['crmid'], 'field' => $field];
					}
				}
			}
		}
		return $linked;
	}

	private static function normalize(string $email): string
	{
		return strtolower(trim($email));
	}
}
