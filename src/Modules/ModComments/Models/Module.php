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

class Module extends \App\Modules\Vtiger\Models\Module
{

	/**
	 * Function to get the Quick Links for the module
	 * @param <Array> $linkParams
	 * @return <Array> List of \App\Modules\Vtiger\Models\Link instances
	 */
	public function getSideBarLinks($linkParams)
	{
		$links = parent::getSideBarLinks($linkParams);
		unset($links['SIDEBARLINK']);
		return $links;
	}

	/**
	 * Function to get the create url with parent id set
	 * @param <type> $parentRecord	- parent record for which comment need to be added
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
	 * @return <Array>
	 */
	public function getSettingLinks()
	{
		require_once ROOT_DIRECTORY . '/src/Modules/com_vtiger_workflow/VTWorkflowUtils.php';

		$editWorkflowsImagePath = Vtiger_Theme::getImagePath('EditWorkflows.png');
		$settingsLinks = array();


		if (VTWorkflowUtils::checkModuleWorkflow($this->getName())) {
			$settingsLinks[] = array(
				'linktype' => 'LISTVIEWSETTING',
				'linklabel' => 'LBL_EDIT_WORKFLOWS',
				'linkurl' => 'index.php?parent=Settings&module=Workflows&view=List&sourceModule=' . $this->getName(),
				'linkicon' => $editWorkflowsImagePath
			);
		}
		return $settingsLinks;
	}

	/**
	 * Delete coments associated with module
	 * @param vtlib\Module Instnace of module to use
	 */
	static function deleteForModule($moduleInstance)
	{
		$db = \App\database\PearDatabase::getInstance();
		$db->delete('vtiger_modcomments', 'related_to IN(SELECT crmid FROM vtiger_crmentity WHERE setype=?)', [$moduleInstance->name]);
	}
}
