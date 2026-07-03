<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * @project FreeCRM
 * @author bmankowski@gmail.com
 * @copyright (c) FreeCRM
 * @license FreeCRM Public License 1.1
 */

declare(strict_types=1);

namespace App\Modules\EmailTemplates\Models;

class RecruitmentTemplate
{
	private const RECRUITMENT_MODULE = 'ProjektyRekrutacyjne';
	private const MATRIX_TABLE = 'u_yf_recruitment_status_transition_mail';

	/**
	 * @return list<string>
	 */
	public static function getDistinctShortNames(): array
	{
		$names = (new \App\Db\Query())
			->select(['sys_name'])
			->from('u_yf_emailtemplates')
			->innerJoin('vtiger_crmentity', 'u_yf_emailtemplates.emailtemplatesid = vtiger_crmentity.crmid')
			->where(['vtiger_crmentity.deleted' => 0])
			->andWhere(TemplateModule::sqlMatchesColumn('u_yf_emailtemplates.modules', self::RECRUITMENT_MODULE))
			->andWhere(['not', ['u_yf_emailtemplates.sys_name' => null]])
			->andWhere(['<>', 'u_yf_emailtemplates.sys_name', ''])
			->orderBy(['u_yf_emailtemplates.sys_name' => SORT_ASC])
			->column();

		$result = [];
		foreach ($names as $name) {
			$name = trim((string) $name);
			if ($name !== '' && !\in_array($name, $result, true)) {
				$result[] = $name;
			}
		}

		return $result;
	}

	public static function isShortNameUsedInMatrix(string $sysName): bool
	{
		$sysName = trim($sysName);
		if ($sysName === '') {
			return false;
		}

		return (new \App\Db\Query())
			->from(self::MATRIX_TABLE)
			->where(['short_name' => $sysName])
			->exists();
	}

	public static function shortNameExists(string $sysName): bool
	{
		$sysName = trim($sysName);
		if ($sysName === '') {
			return false;
		}

		return (new \App\Db\Query())
			->from('u_yf_emailtemplates')
			->innerJoin('vtiger_crmentity', 'u_yf_emailtemplates.emailtemplatesid = vtiger_crmentity.crmid')
			->where([
				'vtiger_crmentity.deleted' => 0,
				'u_yf_emailtemplates.sys_name' => $sysName,
			])
			->andWhere(TemplateModule::sqlMatchesColumn('u_yf_emailtemplates.modules', self::RECRUITMENT_MODULE))
			->exists();
	}

	public static function findForShortNameAndAccount(string $sysName, int $accountId): ?int
	{
		if ($accountId <= 0) {
			return null;
		}

		$id = (new \App\Db\Query())
			->select(['u_yf_emailtemplates.emailtemplatesid'])
			->from('u_yf_emailtemplates')
			->innerJoin('vtiger_crmentity', 'u_yf_emailtemplates.emailtemplatesid = vtiger_crmentity.crmid')
			->where([
				'vtiger_crmentity.deleted' => 0,
				'u_yf_emailtemplates.sys_name' => $sysName,
			])
			->andWhere(TemplateModule::sqlMatchesColumn('u_yf_emailtemplates.modules', self::RECRUITMENT_MODULE))
			->andWhere(TemplateAccount::accountIdInColumnCondition($accountId))
			->scalar();

		$templateId = (int) $id;

		return $templateId > 0 ? $templateId : null;
	}

	public static function findGlobalForShortName(string $sysName): ?int
	{
		$id = (new \App\Db\Query())
			->select(['t.emailtemplatesid'])
			->from(['t' => 'u_yf_emailtemplates'])
			->innerJoin('vtiger_crmentity', 't.emailtemplatesid = vtiger_crmentity.crmid')
			->where([
				'vtiger_crmentity.deleted' => 0,
				't.sys_name' => $sysName,
			])
			->andWhere(TemplateModule::sqlMatchesColumn('t.modules', self::RECRUITMENT_MODULE))
			->andWhere(TemplateAccount::globalAccountCondition('t.account_id'))
			->scalar();

		$templateId = (int) $id;

		return $templateId > 0 ? $templateId : null;
	}

	/**
	 * @param list<string> $shortNames
	 * @return list<int>
	 */
	public static function resolveShortNamesForAccount(array $shortNames, int $accountId): array
	{
		/** @var list<array{id: int, accountSpecific: bool}> $resolved */
		$resolved = [];
		foreach ($shortNames as $shortName) {
			$shortName = trim((string) $shortName);
			if ($shortName === '') {
				continue;
			}
			$templateId = self::findForShortNameAndAccount($shortName, $accountId);
			$accountSpecific = $templateId !== null;
			if (!$accountSpecific) {
				$templateId = self::findGlobalForShortName($shortName);
			}
			if ($templateId === null) {
				continue;
			}
			$resolved[] = ['id' => $templateId, 'accountSpecific' => $accountSpecific];
		}

		if ($resolved === []) {
			return [];
		}

		$hasAccountSpecific = false;
		foreach ($resolved as $row) {
			if ($row['accountSpecific']) {
				$hasAccountSpecific = true;
				break;
			}
		}

		$ids = [];
		foreach ($resolved as $row) {
			if ($hasAccountSpecific && !$row['accountSpecific']) {
				continue;
			}
			if (!\in_array($row['id'], $ids, true)) {
				$ids[] = $row['id'];
			}
		}

		return $ids;
	}

	/**
	 * @param array<int, array<string, mixed>> $templateList
	 * @return array<int, array<string, mixed>>
	 */
	public static function filterTemplateListForAccount(array $templateList, int $accountId): array
	{
		if ($accountId <= 0 || $templateList === []) {
			return $templateList;
		}

		$templateIds = [];
		foreach ($templateList as $row) {
			if (!empty($row['id'])) {
				$templateIds[] = (int) $row['id'];
			}
		}
		if ($templateIds === []) {
			return $templateList;
		}

		$rows = (new \App\Db\Query())
			->select(['emailtemplatesid', 'account_id'])
			->from('u_yf_emailtemplates')
			->where(['emailtemplatesid' => $templateIds])
			->all();

		$globalIds = [];
		$linkedSet = [];
		foreach ($rows as $row) {
			$id = (int) ($row['emailtemplatesid'] ?? 0);
			if ($id <= 0) {
				continue;
			}
			$accountIds = TemplateAccount::parseAccountIds($row['account_id'] ?? '');
			if ($accountIds === []) {
				$globalIds[$id] = true;
			} elseif (\in_array($accountId, $accountIds, true)) {
				$linkedSet[$id] = true;
			}
		}

		$hasAccountSpecific = $linkedSet !== [];

		$filtered = [];
		foreach ($templateList as $row) {
			$id = (int) ($row['id'] ?? 0);
			if ($id <= 0) {
				continue;
			}
			if ($hasAccountSpecific) {
				if (isset($linkedSet[$id])) {
					$filtered[] = $row;
				}
			} elseif (isset($globalIds[$id])) {
				$filtered[] = $row;
			}
		}

		return $filtered;
	}
}
