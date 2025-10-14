<?php

namespace FreeCRM\Modules\Settings\ModuleManager\Actions;



/**
 * Library action class
 * @package YetiForce.Action
 * @license licenses/License.html
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */
class Library extends \FreeCRM\Modules\Settings\Vtiger\Views\IndexAjax
{

	public function __construct()
	{
		parent::__construct();
		$this->exposeMethod('download');
		$this->exposeMethod('update');
	}

	public function process(\FreeCRM\Http\Vtiger_Request $request)
	{
		$mode = $request->getMode();
		if (!empty($mode)) {
			echo $this->invokeExposedMethod($mode, $request);
			return;
		}
	}

	/**
	 * Function to update library
	 * @param \FreeCRM\Http\Vtiger_Request $request
	 */
	public function update(\FreeCRM\Http\Vtiger_Request $request)
	{
		\FreeCRM\Modules\Settings\ModuleManager\Models\Library::update($request->get('name'));
		header("Location: index.php?module=ModuleManager&parent=Settings&view=List");
	}

	/**
	 * Function to download library
	 * @param \FreeCRM\Http\Vtiger_Request $request
	 */
	public function download(\FreeCRM\Http\Vtiger_Request $request)
	{
		\FreeCRM\Modules\Settings\ModuleManager\Models\Library::download($request->get('name'));
		header("Location: index.php?module=ModuleManager&parent=Settings&view=List");
	}
}
