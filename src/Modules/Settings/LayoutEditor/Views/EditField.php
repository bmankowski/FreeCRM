<?php

namespace App\Modules\Settings\LayoutEditor\Views;


/**
 * EditField View Class
 * @package YetiForce.Settings.View
 * @license licenses/License.html
 * @author Tomasz Kur <t.kur@yetiforce.com>
 */

/**
 * EditField View Class
 */
class EditField extends \App\Modules\Settings\Base\Views\BasicModal
{
	/**
	 * Check permission to view
	 * @param \App\Http\Vtiger_Request $request
	 * @throws \App\Exceptions\NoPermittedForAdmin
	 */
	public function checkPermission(\App\Http\Vtiger_Request $request)
	{
		$currentUserModel = $request->getUser();
		if (!$currentUserModel->isAdminUser() && !\App\Modules\Settings\LayoutEditor\Models\Field::getInstance($request->get('fieldId')->isEditable())) {
			throw new \App\Exceptions\NoPermittedForAdmin('LBL_PERMISSION_DENIED');
		}
	}
	
	/**
	 * Main proccess view
	 * @param \App\Http\Vtiger_Request $request
	 */
	public function process(\App\Http\Vtiger_Request $request)
	{
		$this->preProcess($request);
		$qualifiedModuleName = $request->getModule(false);
		$fieldId = $request->get('fieldId');
		$fieldModel = \App\Modules\Settings\LayoutEditor\Models\Field::getInstance($fieldId);
		$viewer = $this->getViewer($request);
		$viewer->assign('FIELD_MODEL', $fieldModel);
		$viewer->assign('SELECTED_MODULE_NAME', $fieldModel->getModule()->getName());
		$viewer->view('EditField.tpl', $qualifiedModuleName);
		$this->postProcess($request);
	}
}
