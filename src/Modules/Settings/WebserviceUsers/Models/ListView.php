<?php

namespace App\Modules\Settings\WebserviceUsers\Models;



/**
 * WebserviceUsers ListView Model Class
 * @package YetiForce.Settings.Model
 * @license licenses/License.html
 * @author Radosław Skrzypczak <r.skrzypczak@yetiforce.com>
 */

class ListView extends \\App\Modules\Settings\Vtiger\Models\ListView
{

	/**
	 * Function sets module instance
	 * @param string $name
	 * @return $this
	 */
	public function setModule($name)
	{
		$modelClassName = \App\Vtiger_Loader::getComponentClassName('Model', 'Module', $name);
		$this->module = new $modelClassName();
		$this->module->typeApi = \App\Http\AppRequest::get('typeApi');
		return $this;
	}

	/**
	 * Function to get Basic links
	 * @return array of Basic links
	 */
	public function getBasicLinks()
	{
		$basicLinks = [];
		$moduleModel = $this->getModule();
		if ($moduleModel->hasCreatePermissions())
			$basicLinks[] = [
				'linktype' => 'LISTVIEWBASIC',
				'linklabel' => 'LBL_ADD_RECORD',
				'linkdata' => ['url' => $moduleModel->getEditViewUrl()],
				'linkicon' => 'glyphicon glyphicon-plus',
				'linkclass' => 'btn-success addRecord',
				'showLabel' => 1,
				'modalView' => true
			];

		return $basicLinks;
	}
}
