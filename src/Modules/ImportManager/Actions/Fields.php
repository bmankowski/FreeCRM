<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * AJAX action returning metadata necessary for mapping UI.
 */

declare(strict_types=1);

namespace App\Modules\ImportManager\Actions;

use App\Base\Controllers\BaseActionController;
use App\Modules\ImportManager\Services\ConfigProvider;

class Fields extends BaseActionController
{
	private ConfigProvider $config;

	public function __construct()
	{
		parent::__construct();
		$this->config = new ConfigProvider();
	}

	public function checkPermission(\App\Http\Vtiger_Request $request)
	{
		$moduleName = $request->get('target_module');
		$privileges = \App\Modules\Users\Models\Privileges::getCurrentUserPrivilegesModel();
		if (!$moduleName || !$privileges || !$privileges->hasModulePermission($moduleName)) {
			throw new \App\Exceptions\NoPermitted('LBL_PERMISSION_DENIED');
		}
	}

	public function process(\App\Http\Vtiger_Request $request)
	{
		$response = new \App\Http\Vtiger_Response();

		try {
			$moduleName = $request->get('target_module');
			$moduleModel = \App\Modules\Base\Models\Module::getInstance($moduleName);
			if (!$moduleModel) {
				throw new \RuntimeException('Nie można odnaleźć wskazanego modułu.');
			}

			$result = [
				'module' => $moduleName,
				'fields' => $this->formatFields($moduleModel),
				'duplicateSets' => $this->config->getDuplicateConfig($moduleName),
			];

			$response->setResult($result);
		} catch (\Throwable $exception) {
			\App\Log\Log::error('ImportManager Fields action failed: ' . $exception->getMessage(), 'ImportManager');
			$response->setError(500, $exception->getMessage());
		}

		$response->emit();
	}

	private function formatFields(\App\Modules\Base\Models\Module $moduleModel): array
	{
		$fields = [];
		foreach ($moduleModel->getFields() as $fieldModel) {
			if (!$fieldModel->isActiveField() || !$fieldModel->isEditable()) {
				continue;
			}

			$fields[] = [
				'name' => $fieldModel->getName(),
				'label' => \App\Language::translate($fieldModel->getFieldLabel(), $moduleModel->getName()),
				'mandatory' => $fieldModel->isMandatory(),
				'uitype' => (int) $fieldModel->getUIType(),
				'type' => $fieldModel->getFieldDataType(),
				'picklistValues' => $fieldModel->getPicklistValues(),
				'referenceModules' => $this->resolveReferenceModules($fieldModel),
			];
		}

		usort($fields, static fn($a, $b) => strcasecmp($a['label'], $b['label']));
		return $fields;
	}

	private function resolveReferenceModules(\App\Modules\Base\Models\Field $field): array
	{
		if ($field->getFieldDataType() !== 'reference') {
			return [];
		}
		return $field->getReferenceList();
	}
}

