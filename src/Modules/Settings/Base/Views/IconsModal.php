<?php

namespace App\Modules\Settings\Base\Views;



/**
 * Icons Modal View Class
 * @package YetiForce.ModalView
 * @license licenses/License.html
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */
class IconsModal extends \App\Modules\Base\Views\BasicModal
{

	public function checkPermission(\App\Http\Vtiger_Request $request)
	{
		$currentUserModel = $request->getUser();
		if (!$currentUserModel->isAdminUser()) {
			throw new \App\Exceptions\NoPermittedForAdmin('LBL_PERMISSION_DENIED');
		}
	}

	public function process(\App\Http\Vtiger_Request $request)
	{
		$this->preProcess($request);
		$viewer = $this->getViewer($request);

		$qualifiedModuleName = $request->getModule(false);
		$viewer = $this->getViewer($request);
		$viewer->assign('QUALIFIED_MODULE', $qualifiedModuleName);
		
		// Prepare IconsModal-specific data for IconsModal template
		$this->prepareIconsModalData($viewer);
		
		$viewer->view('IconsModal.tpl', $qualifiedModuleName);

		$this->postProcess($request);
	}
	
	/**
	 * Prepare data for IconsModal template
	 * Moves function calls from template to controller for better MVC separation
	 */
	protected function prepareIconsModalData($viewer)
	{
		$viewer->assign('GLYPHICON_ICONS', \App\Modules\Settings\Base\Models\Icons::getGlyphicon());
		$viewer->assign('USER_ICONS', \App\Modules\Settings\Base\Models\Icons::getUserIcon());
		$viewer->assign('ADMIN_ICONS', \App\Modules\Settings\Base\Models\Icons::getAdminIcon());
		$viewer->assign('ADDITIONAL_ICONS', \App\Modules\Settings\Base\Models\Icons::getAdditionalIcon());
		$viewer->assign('FONTAWESOME_ICONS', \App\Modules\Settings\Base\Models\Icons::getFontAwesomeIcon());
		$viewer->assign('IMAGE_ICONS', \App\Modules\Settings\Base\Models\Icons::getImageIcon());
	}

	public function getModalScripts(\App\Http\Vtiger_Request $request)
	{
		$scripts = array(
			'modules.Settings.Vtiger.resources.IconsModal'
		);
		$scriptInstances = $this->checkAndConvertJsScripts($scripts);
		return $scriptInstances;
	}
}
