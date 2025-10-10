<?php

namespace FreeCRM\Modules\Vtiger\Models;

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */

/**
 * Vtiger EditView Model Class
 */
class EditView extends Model
{

	/**
	 * Function to get the instance
	 * @param string $moduleName - module name
	 * @param string $recordId - record id
	 * @return <\FreeCRM\Modules\Vtiger\Models\DetailView>
	 */
	public static function getInstance($moduleName, $recordId)
	{
		$modelClassName = \FreeCRM\Loader::getComponentClassName('Model', 'EditView', $moduleName);
		$instance = new $modelClassName();
		$moduleModel = \FreeCRM\Modules\Vtiger\Models\Module::getInstance($moduleName);
		return $instance->set('module', $moduleModel);
	}

	/**
	 * Function to get the Module Model
	 * @return \FreeCRM\Modules\Vtiger\Models\Module instance
	 */
	public function getModule()
	{
		return $this->get('module');
	}

	/**
	 * Function to get the list of listview links for the module
	 * @param <Array> $linkParams
	 * @return <Array> - Associate array of Link Type to List of \FreeCRM\Modules\Vtiger\Models\Link instances
	 */
	public function getEditViewLinks($linkParams)
	{
		$links = \FreeCRM\Modules\Vtiger\Models\Link::getAllByType($this->getModule()->getId(), ['EDIT_VIEW_HEADER'], $linkParams);
		return $links;
	}
}
