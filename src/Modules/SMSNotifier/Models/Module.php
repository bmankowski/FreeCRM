<?php

namespace App\Modules\SMSNotifier\Models;

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */

class Module extends \App\Modules\Base\Models\Module
{

	/**
	 * Function to check whether the module is an entity type module or not
	 * @return boolean true/false
	 */
	public function isQuickCreateSupported()
	{
		//SMSNotifier module is not enabled for quick create
		return false;
	}

	/**
	 * Function to check whether the module is summary view supported
	 * @return boolean - true/false
	 */
	public function isSummaryViewSupported()
	{
		return false;
	}

	/**
	 * Function to get the module is permitted to specific action
	 * @param string $actionName
	 * @return bool
	 */
	public function isPermitted($actionName)
	{
		if ($actionName === 'EditView' || $actionName === 'Edit' || $actionName === 'CreateView') {
			return false;
		}
		return \App\Modules\Users\Models\Privileges::isPermitted($this->getName(), $actionName);
	}

	/**
	 * Function to get Settings links
	 * @return array
	 */
	public function getSettingLinks()
	{

		$editWorkflowsImagePath = \App\Runtime\Vtiger_Theme::getImagePath('EditWorkflows.png');
		$settingsLinks = array();


		if (\App\Modules\Workflow\VTWorkflowUtils::checkModuleWorkflow($this->getName())) {
			$settingsLinks[] = array(
				'linktype' => 'LISTVIEWSETTING',
				'linklabel' => 'LBL_EDIT_WORKFLOWS',
				'linkurl' => 'index.php?parent=Settings&module=Workflows&view=ListView&sourceModule=' . $this->getName(),
				'linkicon' => $editWorkflowsImagePath
			);
		}

		$settingsLinks[] = array(
			'linktype' => 'LISTVIEWSETTING',
			'linklabel' => \App\Runtime\Vtiger_Language_Handler::translate('LBL_SERVER_CONFIG', $this->getName()),
			'linkurl' => 'index.php?module=SMSNotifier&parent=Settings&view=ListView',
			'linkicon' => ''
		);
		return $settingsLinks;
	}
}
