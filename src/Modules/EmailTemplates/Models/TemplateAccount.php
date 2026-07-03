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

use App\Modules\Base\UiTypes\MultiReference;

class TemplateAccount
{
	private const RECRUITMENT_MODULE = 'ProjektyRekrutacyjne';
	private const ACCOUNT_COLUMN = 'u_yf_emailtemplates.account_id';

	/**
	 * @return list<int>
	 */
	public static function parseAccountIds(mixed $value): array
	{
		return MultiReference::parseIds($value);
	}

	public static function isGlobalValue(mixed $value): bool
	{
		return self::parseAccountIds($value) === [];
	}

	/**
	 * @return list<int>
	 */
	public static function getAccountIdsForTemplate(int $templateId): array
	{
		if ($templateId <= 0) {
			return [];
		}

		$value = (new \App\Db\Query())
			->select(['account_id'])
			->from('u_yf_emailtemplates')
			->where(['emailtemplatesid' => $templateId])
			->scalar();

		return self::parseAccountIds($value);
	}

	public static function isGlobal(int $templateId): bool
	{
		return self::getAccountIdsForTemplate($templateId) === [];
	}

	public static function assertNoSysNameOverlap(int $templateId, string $sysName, mixed $accountIdsValue): void
	{
		$sysName = trim($sysName);
		if ($sysName === '') {
			return;
		}

		$accountIds = self::parseAccountIds($accountIdsValue);

		$query = (new \App\Db\Query())
			->select(['u_yf_emailtemplates.emailtemplatesid'])
			->from('u_yf_emailtemplates')
			->innerJoin('vtiger_crmentity', 'u_yf_emailtemplates.emailtemplatesid = vtiger_crmentity.crmid')
			->where([
				'vtiger_crmentity.deleted' => 0,
				'u_yf_emailtemplates.sys_name' => $sysName,
			])
			->andWhere(TemplateModule::sqlMatchesColumn('u_yf_emailtemplates.modules', self::RECRUITMENT_MODULE))
			->andWhere(['<>', 'u_yf_emailtemplates.emailtemplatesid', $templateId]);

		if ($accountIds === []) {
			$query->andWhere(self::globalAccountCondition());
		} else {
			$overlap = ['or'];
			foreach ($accountIds as $accountId) {
				$param = ':aid_' . $accountId;
				$overlap[] = new \yii\db\Expression(
					'FIND_IN_SET(' . $param . ', ' . self::ACCOUNT_COLUMN . ') > 0',
					[$param => (string) $accountId]
				);
			}
			$query->andWhere($overlap);
		}

		if ($query->scalar()) {
			throw new \App\Exceptions\AppException('LBL_ERR_SYS_NAME_ACCOUNT_OVERLAP');
		}
	}

	/**
	 * @return array<int|string, mixed>
	 */
	public static function globalAccountCondition(string $column = self::ACCOUNT_COLUMN): array
	{
		return [
			'or',
			[$column => null],
			[$column => ''],
			[$column => '0'],
		];
	}

	public static function accountIdInColumnCondition(int $accountId, string $column = self::ACCOUNT_COLUMN): \yii\db\Expression
	{
		return new \yii\db\Expression(
			'FIND_IN_SET(:accountId, ' . $column . ') > 0',
			[':accountId' => (string) $accountId]
		);
	}
}
