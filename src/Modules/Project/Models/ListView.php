<?php

namespace App\Modules\Project\Models;

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */

/**
 * ListView Model Class for Project module
 */
class ListView extends \App\Modules\Base\Models\ListView
{

	/**
	 * Function to get the list of listview links
	 * @param array $linkParams Parameters to be replaced in the link template
	 * @return array - an array of \App\Modules\Base\Models\Link instances
	 */
	public function getListViewLinks($linkParams, ?\App\Modules\Users\Models\Record $currentUser = null)
	{
		$links = parent::getListViewLinks($linkParams, $currentUser);

		$quickLinks = array(
			array(
				'linktype' => 'LISTVIEWQUICK',
				'linklabel' => 'Tasks List',
				'linkurl' => $this->getModule()->getDefaultUrl(),
				'linkicon' => ''
			),
		);
		foreach ($quickLinks as $quickLink) {
			$links['LISTVIEWQUICK'][] = \App\Modules\Base\Models\Link::getInstanceFromValues($quickLink);
		}

		return $links;
	}
}
