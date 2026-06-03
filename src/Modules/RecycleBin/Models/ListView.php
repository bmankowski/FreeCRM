<?php

namespace App\Modules\RecycleBin\Models;

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */

class ListView extends \App\Modules\Base\Models\ListView
{

	/**
	 * Static Function to get the Instance of Vtiger ListView model for a given module and custom view
	 * @param string $moduleName - Module Name
	 * @param string $sourceModule - Source Module Name
	 * @return \App\Modules\Base\Models\ListView instance
	 */
	public static function getInstance($moduleName, $sourceModule = 0)
	{
		$modelClassName = \App\Core\Loader::getComponentClassName('Model', 'ListView', $moduleName);
		$instance = new $modelClassName();

		$sourceModuleModel = \App\Modules\Base\Models\Module::getInstance($sourceModule);
		$queryGenerator = new \App\QueryField\QueryGenerator($sourceModuleModel->get('name'));
		$cvidObj = \App\Modules\CustomView\Models\Record::getAllFilterByModule($sourceModuleModel->get('name'));
		$viewId = $cvidObj->getId('cvid');
		$queryGenerator->initForCustomViewById($viewId);
		return $instance->set('module', $sourceModuleModel)->set('query_generator', $queryGenerator);
	}

	/**
	 * Load list view conditions
	 * @param string $moduleName
	 */
	public function loadListViewCondition()
	{
		$queryGenerator = $this->get('query_generator');
		$queryGenerator->deletedCondition = false;
		$queryGenerator->addNativeCondition(['vtiger_crmentity.deleted' => 1]);
		parent::loadListViewCondition();
	}

	/**
	 * Function to get the list view entries
	 * @param \App\Modules\Base\Models\Paging $pagingModel
	 * @return array - Associative array of record id mapped to \App\Modules\Base\Models\Record instance.
	 */
	public function getListViewCount(): int
	{
		$queryGenerator = $this->get('query_generator');
		$queryGenerator->deletedCondition = false;
		$queryGenerator->addNativeCondition(['vtiger_crmentity.deleted' => 1]);
		return parent::getListViewCount();
	}
}
