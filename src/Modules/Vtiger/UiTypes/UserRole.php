<?php

namespace FreeCRM\Modules\Vtiger\UiTypes;

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Contributor(s): YetiForce.com
 * *********************************************************************************** */

class UserRole extends Base
{

	/**
	 * Function to get display value
	 * @param string $value
	 * @param int $recordId
	 * @param \FreeCRM\Modules\Vtiger\Models\Record $recordInstance
	 * @param bool $rawText
	 * @return string
	 */
	public function getDisplayValue($value, $recordId = false, $recordInstance = false, $rawText = false)
	{
		$displayValue = \FreeCRM\Runtime\Vtiger_Language_Handler::translate(\App\PrivilegeUtil::getRoleName($value), $this->get('field')->getModuleName());
		$currentUserModel = \App\User::getCurrentUserModel();
		if ($currentUserModel->isAdmin() && $rawText === false) {
			$roleRecordModel = new \FreeCRM\Modules\Settings\Roles\Models\Record();
			$roleRecordModel->set('roleid', $value);
			return '<a href="' . $roleRecordModel->getEditViewUrl() . '">' . \vtlib\Functions::textLength($displayValue) . '</a>';
		}
		return $displayValue;
	}

	/**
	 * Function to get all the available picklist values for the current field
	 * @return string[]
	 */
	public function getPicklistValues()
	{
		$roleModels = \FreeCRM\Modules\Settings\Roles\Models\Record::getAll();
		$roles = [];
		foreach ($roleModels as $roleId => $roleModel) {
			$roles[$roleId] = \FreeCRM\Runtime\Vtiger_Language_Handler::translate($roleModel->getName(), $this->get('field')->getModuleName());
		}
		return $roles;
	}

	/**
	 * Function searches for value data 
	 * @param string $value
	 * @return string[]
	 */
	public function getSearchValues($value)
	{
		$roles = (new \App\Db\Query())->select(['roleid', 'rolename'])->from('vtiger_role')->where(['like', 'rolename', $value])
				->createCommand()->queryAllByGroup();
		return $roles;
	}

	/**
	 * Function to get the Template name for the current UI Type object
	 * @return string - Template Name
	 */
	public function getListSearchTemplateName()
	{
		return 'uitypes/UserRoleFieldSearchView.tpl';
	}
}
