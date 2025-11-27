<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * Provides helper methods for discovering modules available for import.
 */

declare(strict_types=1);

namespace App\Modules\ImportManager\Services;

class ModuleDiscovery
{
	/**
	 * Return modules that are entity-based and accessible for ImportData action.
	 */
	public function getAvailableModules(): array
	{
		$modules = \App\Modules\Base\Models\Module::getEntityModules();
		$privileges = \App\Modules\Users\Models\Privileges::getCurrentUserPrivilegesModel();
		$allowed = [];

		foreach ($modules as $moduleModel) {
			$moduleName = $moduleModel->getName();

			if (!$moduleModel->isActive()) {
				continue;
			}

			if ($moduleName === 'Users') {
				continue;
			}

			if ($privileges && (
				!$privileges->hasModulePermission($moduleName)
				|| !$privileges->hasModuleActionPermission($moduleName, 'ImportData')
			)) {
				continue;
			}

			$allowed[] = [
				'name' => $moduleName,
				'label' => \App\Runtime\Vtiger_Language_Handler::translate($moduleName, $moduleName),
				'tabid' => $moduleModel->getId(),
			];
		}

		usort($allowed, static fn($a, $b) => strcasecmp($a['label'], $b['label']));

		return $allowed;
	}
}

