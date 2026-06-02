<?php

namespace App\Modules\ModComments\Models;

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
	 * Function to get the Quick Links for the module
	 * @param array $linkParams
	 * @return array List of \App\Modules\Base\Models\Link instances
	 */
	public function getSideBarLinks($linkParams, ?\App\Modules\Users\Models\Record $currentUser = null)
	{
		$links = parent::getSideBarLinks($linkParams, $currentUser);
		unset($links['SIDEBARLINK']);
		return $links;
	}

	/**
	 * Function to get the create url with parent id set
	 * @param mixed $parentRecord	- parent record for which comment need to be added
	 * @return string Url
	 */
	public function getCreateRecordUrlWithParent($parentRecord)
	{
		$createRecordUrl = $this->getCreateRecordUrl();
		$createRecordUrlWithParent = $createRecordUrl . '&parent_id=' . $parentRecord->getId();
		return $createRecordUrlWithParent;
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
		return $settingsLinks;
	}

	/**
	 * Delete coments associated with module
	 * @param \App\Modules\Base\Models\Module|\vtlib\Module Instance of module to use
	 */
	static function deleteForModule($moduleInstance)
	{
		$moduleName = $moduleInstance instanceof \App\Modules\Base\Models\Module ? $moduleInstance->getName() : $moduleInstance->name;
		$db = \App\Database\PearDatabase::getInstance();
		$db->delete('vtiger_modcomments', 'related_to IN(SELECT crmid FROM vtiger_crmentity WHERE setype=?)', [$moduleName]);
	}
}
