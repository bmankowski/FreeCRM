<?php

namespace App\Modules\Settings\ModuleManager\Actions;



/**
 * Library action class
 * @package YetiForce.Action
 * @license licenses/License.html
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */
class Library extends \App\Modules\Settings\Base\Views\IndexAjax
{

	public function __construct()
	{
		parent::__construct();
		$this->exposeMethod('download');
		$this->exposeMethod('update');
	}

	public function process(\App\Http\Vtiger_Request $request)
	{
		$mode = $request->getMode();
		if (!empty($mode)) {
			echo $this->invokeExposedMethod($mode, $request);
			return;
		}
	}

	/**
	 * Function to update library
	 * @param \App\Http\Vtiger_Request $request
	 */
	public function update(\App\Http\Vtiger_Request $request)
	{
		\App\Modules\Settings\ModuleManager\Models\Library::update($request->get('name'));
		header("Location: index.php?module=ModuleManager&parent=Settings&view=List");
	}

	/**
	 * Function to download library
	 * @param \App\Http\Vtiger_Request $request
	 */
	public function download(\App\Http\Vtiger_Request $request)
	{
		\App\Modules\Settings\ModuleManager\Models\Library::download($request->get('name'));
		header("Location: index.php?module=ModuleManager&parent=Settings&view=List");
	}
}
