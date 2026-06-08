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

namespace App\Modules\EmailTemplates\Actions;

use App\Http\Vtiger_Request;
use App\Modules\LinkAction\Services\LinkActionConfig;

class ParsePreview extends \App\Base\Controllers\BaseActionController
{
	public function checkPermission(Vtiger_Request $request): bool
	{
		if (!\App\Security\Privilege::isPermitted('EmailTemplates')) {
			throw new \App\Exceptions\NoPermitted('LBL_PERMISSION_DENIED');
		}
		return true;
	}

	public function process(Vtiger_Request $request): void
	{
		$content = (string) $request->getRaw('content');
		$moduleName = trim((string) $request->get('moduleName'));
		$parserType = (string) ($request->get('parserType') ?: 'mail');
		$recordId = $request->getInteger('recordId');

		$recordModel = $this->resolveRecordModel($moduleName, $recordId);
		if ($recordModel) {
			$parser = \App\Email\EmailParser::getInstanceByModel($recordModel);
		} else {
			$parser = \App\TextParser\TextParser::getInstance($moduleName !== '' ? $moduleName : 'Vtiger');
		}
		$parser->setType($parserType);
		$parsed = $parser->setContent($content)->parse()->getContent();

		$response = new \App\Http\Vtiger_Response();
		$response->setResult([
			'success' => true,
			'content' => $parsed,
			'sampleRecordId' => $recordModel ? (int) $recordModel->getId() : 0,
			'sampleRecordModule' => $recordModel ? $recordModel->getModuleName() : '',
		]);
		$response->emit();
	}

	private function resolveRecordModel(string $moduleName, int $recordId): ?\App\Modules\Base\Models\Record
	{
		if ($recordId > 0 && $moduleName !== '' && \App\Records\Record::isExists($recordId, $moduleName)) {
			if (!\App\Security\Privilege::isPermitted($moduleName, 'DetailView', $recordId)) {
				throw new \App\Exceptions\NoPermittedToRecord('LBL_NO_PERMISSIONS_FOR_THE_RECORD');
			}
			return \App\Modules\Base\Models\Record::getInstanceById($recordId, $moduleName);
		}
		if ($moduleName === '') {
			return null;
		}
		$sampleId = $this->resolveSampleRecordId($moduleName);
		if ($sampleId <= 0) {
			return null;
		}
		if (!\App\Security\Privilege::isPermitted($moduleName, 'DetailView', $sampleId)) {
			return null;
		}
		return \App\Modules\Base\Models\Record::getInstanceById($sampleId, $moduleName);
	}

	private function resolveSampleRecordId(string $moduleName): int
	{
		$entity = \App\Utils\ModuleUtils::getEntityInfo($moduleName);
		if (!$entity) {
			return 0;
		}
		$baseTable = (string) ($entity['tablename'] ?? '');
		$idColumn = (string) ($entity['entityidfield'] ?? '');
		if ($baseTable === '' || $idColumn === '') {
			return 0;
		}

		$query = (new \App\Db\Query())
			->select(["{$baseTable}.{$idColumn}"])
			->from($baseTable)
			->innerJoin('vtiger_crmentity', "vtiger_crmentity.crmid = {$baseTable}.{$idColumn}")
			->where([
				'vtiger_crmentity.deleted' => 0,
				'vtiger_crmentity.setype' => $moduleName,
			])
			->orderBy(["{$baseTable}.{$idColumn}" => SORT_DESC])
			->limit(1);

		$config = LinkActionConfig::moduleConfig($moduleName);
		$emailField = (string) ($config['default_email_field'] ?? '');
		$cfTable = $baseTable . 'cf';
		if ($emailField !== '' && \App\Db\Db::getInstance()->getSchema()->getTableSchema($cfTable) !== null) {
			$query->innerJoin($cfTable, "{$cfTable}.{$idColumn} = {$baseTable}.{$idColumn}");
			$query->andWhere(['not', ["{$cfTable}.{$emailField}" => '']]);
			$query->andWhere(['not', ["{$cfTable}.{$emailField}" => null]]);
		}

		return (int) $query->scalar();
	}
}
