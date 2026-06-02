<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * @project FreeCRM
 * @author bmankowski@gmail.com
 * @copyright (c) FreeCRM
 * @license FreeCRM Public License 1.1
 */

namespace App\Modules\ProjektyRekrutacyjne\Models;

/**
 * Virtual relation-table field (not in vtiger_field).
 */
class RelationField extends \App\Modules\Base\Models\Field
{
	public function getModuleName(): ?string
	{
		$moduleName = parent::getModuleName();
		if ($moduleName !== null) {
			return $moduleName;
		}

		$module = $this->getModule();
		return $module ? $module->getName() : null;
	}

	public function getPicklistValues($skipCheckingRole = false)
	{
		if ('recruitment_status_rel' === $this->getName()) {
			return \App\Modules\Settings\Workflows\Models\RelationTrigger::getRecruitmentStatusOptions();
		}

		return parent::getPicklistValues($skipCheckingRole);
	}

	public function getPermissions($readOnly = true)
	{
		if ('recruitment_status_rel' === $this->getName()) {
			return true;
		}

		return parent::getPermissions($readOnly);
	}
}
