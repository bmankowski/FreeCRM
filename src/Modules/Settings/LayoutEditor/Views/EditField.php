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
		
		// Prepare LayoutEditor EditField-specific data for EditField template
		$this->prepareLayoutEditorEditFieldData($viewer, $fieldModel);
		
		$viewer->view('EditField.tpl', $qualifiedModuleName);
		$this->postProcess($request);
	}
	
	/**
	 * Prepare data for LayoutEditor EditField template
	 * Moves function calls from template to controller for better MVC separation
	 */
	protected function prepareLayoutEditorEditFieldData($viewer, $fieldModel)
	{
		// Prepare field info JSON with toSafeHTML
		$fieldInfo = $fieldModel->getFieldInfo();
		$fieldInfoJson = \App\Modules\Base\Helpers\Util::toSafeHTML(\App\Utils\Json::encode($fieldInfo));
		$viewer->assign('FIELD_INFO_JSON', $fieldInfoJson);
		
		// Prepare safe HTML for picklist values
		$picklistValues = $fieldModel->getPicklistValues();
		$safePicklistValues = [];
		foreach ($picklistValues as $key => $value) {
			$safePicklistValues[$key] = \App\Modules\Base\Helpers\Util::toSafeHTML($key);
		}
		$viewer->assign('SAFE_PICKLIST_VALUES', $safePicklistValues);
		
		// Prepare display type list
		$viewer->assign('DISPLAY_TYPE', \App\Modules\Base\Models\Field::showDisplayTypeList());
		
		// Prepare developer config flags
		$viewer->assign('CHANGE_GENERATEDTYPE_ENABLED', \App\Core\AppConfig::developer('CHANGE_GENERATEDTYPE'));
		$viewer->assign('CHANGE_VISIBILITY_ENABLED', \App\Core\AppConfig::developer('CHANGE_VISIBILITY'));
	}
}
