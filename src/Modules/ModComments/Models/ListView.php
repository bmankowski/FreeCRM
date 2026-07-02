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
class Model extends \App\Modules\Base\Models\ListView
{

	/**
	 * Function to get the list of listview links for the module
	 * @param array $linkParams
	 * @return array - Associate array of Link Type to List of \App\Modules\Base\Models\Link instances
	 */
	public function getListViewLinks($linkParams, ?\App\Modules\Users\Models\Record $currentUser = null)
	{
		$currentUser ??= \App\User\CurrentUser::get();
		$links = parent::getListViewLinks($linkParams, $currentUser);
		$currentUserModel = $currentUser;
		$moduleModel = $this->getModule();

		unset($links['LISTVIEW']);
		unset($links['LISTVIEWSETTING']);

		if ($currentUserModel->isAdminUser()) {
			$settingsLink = array(
				'linktype' => 'LISTVIEWSETTING',
				'linklabel' => 'LBL_EDIT_WORKFLOWS',
				'linkurl' => 'index.php?parent=Settings&module=Workflow&sourceModule=' . $this->getName(),
				'linkicon' => \App\Runtime\Vtiger_Theme::getThemeImageWebUrl('EditWorkflows.png')
			);
			$links['LISTVIEWSETTING'][] = \App\Modules\Base\Models\Link::getInstanceFromValues($settingsLink);
		}

		return $links;
	}

	/**
	 * Function to get the list of Mass actions for the module
	 * @param array $linkParams
	 * @return array - empty array
	 */
	public function getListViewMassActions($linkParams, ?\App\Modules\Users\Models\Record $currentUser = null)
	{
		return array();
	}
}
