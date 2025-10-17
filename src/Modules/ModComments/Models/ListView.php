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

/**
 * ModComments ListView Model Class
 */
class Model extends \App\Modules\Vtiger\Models\ListView
{

	/**
	 * Function to get the list of listview links for the module
	 * @param <Array> $linkParams
	 * @return <Array> - Associate array of Link Type to List of \App\Modules\Vtiger\Models\Link instances
	 */
	public function getListViewLinks($linkParams)
	{
		$links = parent::getListViewLinks($linkParams);
		$currentUserModel = \App\Modules\Users\Models\Record::getCurrentUserModel();
		$moduleModel = $this->getModule();

		unset($links['LISTVIEW']);
		unset($links['LISTVIEWSETTING']);

		if ($currentUserModel->isAdminUser()) {
			$settingsLink = array(
				'linktype' => 'LISTVIEWSETTING',
				'linklabel' => 'LBL_EDIT_WORKFLOWS',
				'linkurl' => 'index.php?parent=Settings&module=Workflow&sourceModule=' . $this->getName(),
				'linkicon' => Vtiger_Theme::getImagePath('EditWorkflows.png')
			);
			$links['LISTVIEWSETTING'][] = \App\Modules\Vtiger\Models\Link::getInstanceFromValues($settingsLink);
		}

		return $links;
	}

	/**
	 * Function to get the list of Mass actions for the module
	 * @param <Array> $linkParams
	 * @return <Array> - empty array
	 */
	public function getListViewMassActions($linkParams)
	{
		return array();
	}
}
