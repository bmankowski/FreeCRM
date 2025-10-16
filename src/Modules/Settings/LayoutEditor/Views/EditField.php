<?php

namespace FreeCRM\Modules\Settings\LayoutEditor\Views;
use FreeCRM\Modules\Settings\LayoutEditor\Models\Field as Settings_LayoutEditor_Field_Model;


/**
 * EditField View Class
 * @package YetiForce.Settings.View
 * @license licenses/License.html
 * @author Tomasz Kur <t.kur@yetiforce.com>
 */

/**
 * EditField View Class
 */
class EditField extends \FreeCRM\Modules\Settings\Vtiger\Views\BasicModal
{
	/**
	 * Check permission to view
	 * @param \FreeCRM\Http\Vtiger_Request $request
	 * @throws \Exception\NoPermittedForAdmin
	 */
	public function checkPermission(\FreeCRM\Http\Vtiger_Request $request)
	{
		$currentUserModel = \FreeCRM\Modules\Users\Models\Record::getCurrentUserModel();
		if (!$currentUserModel->isAdminUser() && !Settings_LayoutEditor_Field_Model::getInstance($request->get('fieldId')->isEditable())) {
			throw new \Exception\NoPermittedForAdmin('LBL_PERMISSION_DENIED');
		}
	}
	
	/**
	 * Main proccess view
	 * @param \FreeCRM\Http\Vtiger_Request $request
	 */
	public function process(\FreeCRM\Http\Vtiger_Request $request)
	{
		$this->preProcess($request);
		$qualifiedModuleName = $request->getModule(false);
		$fieldId = $request->get('fieldId');
		$fieldModel = Settings_LayoutEditor_Field_Model::getInstance($fieldId);
		$viewer = $this->getViewer($request);
		$viewer->assign('FIELD_MODEL', $fieldModel);
		$viewer->assign('SELECTED_MODULE_NAME', $fieldModel->getModule()->getName());
		$viewer->view('EditField.tpl', $qualifiedModuleName);
		$this->postProcess($request);
	}
}
