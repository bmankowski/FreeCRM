<?php

namespace FreeCRM\Modules\Settings\Picklist\Views;
use FreeCRM\Modules\Settings\PicklistModels\Field;


/* +**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Contributor(s): YetiForce.com
 * ********************************************************************************** */

use FreeCRM\Modules\Settings\Picklist\Models\Module as Settings_Picklist_Module_Model;
class IndexAjax extends \FreeCRM\Modules\Settings\Vtiger\Views\IndexAjax
{

	public function __construct()
	{
		parent::__construct();
		$this->exposeMethod('showEditView');
		$this->exposeMethod('showDeleteView');
		$this->exposeMethod('getPickListDetailsForModule');
		$this->exposeMethod('getPickListValueForField');
		$this->exposeMethod('getPickListValueByRole');
		$this->exposeMethod('showAssignValueToRoleView');
	}

	public function process(\FreeCRM\Http\Vtiger_Request $request)
	{
		$mode = $request->get('mode');
		if ($this->isMethodExposed($mode)) {
			$this->invokeExposedMethod($mode, $request);
		}
	}

	public function showEditView(\FreeCRM\Http\Vtiger_Request $request)
	{
		$module = $request->get('source_module');
		$pickListFieldId = $request->get('pickListFieldId');
		$fieldModel = \FreeCRM\Modules\Settings\Picklist\Models\Field::getInstance($pickListFieldId);
		$valueToEdit = $request->getRaw('fieldValue');

		$selectedFieldEditablePickListValues = \App\Fields\Picklist::getEditablePicklistValues($fieldModel->getName());
		$selectedFieldNonEditablePickListValues = \App\Fields\Picklist::getNonEditablePicklistValues($fieldModel->getName());
		$qualifiedName = $request->getModule(false);
		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();
		$viewer->assign('SOURCE_MODULE', $module);
		$viewer->assign('SOURCE_MODULE_NAME', $module);
		$viewer->assign('FIELD_MODEL', $fieldModel);
		$viewer->assign('FIELD_VALUE', $valueToEdit);
		$viewer->assign('SELECTED_PICKLISTFIELD_EDITABLE_VALUES', $selectedFieldEditablePickListValues);
		$viewer->assign('SELECTED_PICKLISTFIELD_NON_EDITABLE_VALUES', $selectedFieldNonEditablePickListValues);
		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('QUALIFIED_MODULE', $qualifiedName);
		echo $viewer->view('EditView.tpl', $qualifiedName, true);
	}

	public function showDeleteView(\FreeCRM\Http\Vtiger_Request $request)
	{
		$module = $request->get('source_module');
		$pickListFieldId = $request->get('pickListFieldId');
		$fieldModel = \FreeCRM\Modules\Settings\Picklist\Models\Field::getInstance($pickListFieldId);
		$valueToDelete = $request->get('fieldValue');

		$selectedFieldEditablePickListValues = \App\Fields\Picklist::getEditablePicklistValues($fieldModel->getName());
		$selectedFieldNonEditablePickListValues = \App\Fields\Picklist::getNonEditablePicklistValues($fieldModel->getName());
		$selectedFieldEditablePickListValues = array_map('\FreeCRM\Modules\Vtiger\Util::toSafeHTML', $selectedFieldEditablePickListValues);
		if (!empty($selectedFieldNonEditablePickListValues)) {
			$selectedFieldNonEditablePickListValues = array_map('\FreeCRM\Modules\Vtiger\Util::toSafeHTML', $selectedFieldNonEditablePickListValues);
		}

		$qualifiedName = $request->getModule(false);
		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();
		$viewer->assign('SOURCE_MODULE', $module);
		$viewer->assign('SOURCE_MODULE_NAME', $module);
		$viewer->assign('FIELD_MODEL', $fieldModel);

		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('QUALIFIED_MODULE', $qualifiedName);
		$viewer->assign('SELECTED_PICKLISTFIELD_EDITABLE_VALUES', $selectedFieldEditablePickListValues);
		$viewer->assign('SELECTED_PICKLISTFIELD_NON_EDITABLE_VALUES', $selectedFieldNonEditablePickListValues);
		$viewer->assign('FIELD_VALUES', array_map('\FreeCRM\Modules\Vtiger\Util::toSafeHTML', $valueToDelete));
		echo $viewer->view('DeleteView.tpl', $qualifiedName, true);
	}

	public function getPickListDetailsForModule(\FreeCRM\Http\Vtiger_Request $request)
	{
		$sourceModule = $request->get('source_module');
		$moduleModel = Settings_Picklist_Module_Model::getInstance($sourceModule);
		$pickListFields = $moduleModel->getFieldsByType(array('picklist', 'multipicklist'));

		$qualifiedName = $request->getModule(false);

		$viewer = $this->getViewer($request);
		$viewer->assign('PICKLIST_FIELDS', $pickListFields);
		$viewer->assign('SELECTED_MODULE_NAME', $sourceModule);
		$viewer->assign('QUALIFIED_MODULE', $qualifiedName);
		$viewer->view('ModulePickListDetail.tpl', $qualifiedName);
	}

	public function getPickListValueForField(\FreeCRM\Http\Vtiger_Request $request)
	{
		$sourceModule = $request->get('source_module');
		$pickFieldId = $request->get('pickListFieldId');
		$moduleName = $request->getModule();
		$qualifiedName = $request->getModule(false);

		if (!empty($pickFieldId)) {
			$fieldModel = \FreeCRM\Modules\Settings\Picklist\Models\Field::getInstance($pickFieldId);
			$selectedFieldAllPickListValues = \App\Fields\Picklist::getPickListValues($fieldModel->getName());
		}

		$viewer = $this->getViewer($request);
		$viewer->assign('SELECTED_PICKLIST_FIELDMODEL', $fieldModel);
		$viewer->assign('SELECTED_MODULE_NAME', $sourceModule);
		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('QUALIFIED_MODULE', $qualifiedName);
		$viewer->assign('ROLES_LIST', \FreeCRM\Modules\Settings\Roles\Models\Record::getAll());
		$viewer->assign('SELECTED_PICKLISTFIELD_ALL_VALUES', $selectedFieldAllPickListValues);
		$viewer->view('PickListValueDetail.tpl', $qualifiedName);
	}

	public function getPickListValueByRole(\FreeCRM\Http\Vtiger_Request $request)
	{
		$sourceModule = $request->get('sourceModule');
		$pickFieldId = $request->get('pickListFieldId');
		$fieldModel = \FreeCRM\Modules\Settings\Picklist\Models\Field::getInstance($pickFieldId);
		$moduleName = $request->getModule();
		$qualifiedName = $request->getModule(false);

		$userSelectedRoleId = $request->get('rolesSelected');

		$pickListValuesForRole = $fieldModel->getPicklistValuesForRole([$userSelectedRoleId], 'CONJUNCTION');
		$pickListValuesForRole = array_map('\FreeCRM\Modules\Vtiger\Util::toSafeHTML', $pickListValuesForRole);
		$allPickListValues = \App\Fields\Picklist::getPickListValues($fieldModel->getName());
		$allPickListValues = array_map('\FreeCRM\Modules\Vtiger\Util::toSafeHTML', $allPickListValues);

		$viewer = $this->getViewer($request);
		$viewer->assign('SELECTED_PICKLIST_FIELDMODEL', $fieldModel);
		$viewer->assign('SELECTED_MODULE_NAME', $sourceModule);
		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('QUALIFIED_MODULE', $qualifiedName);
		$viewer->assign('ROLE_PICKLIST_VALUES', $pickListValuesForRole);
		$viewer->assign('ALL_PICKLIST_VALUES', $allPickListValues);
		$viewer->view('PickListValueByRole.tpl', $qualifiedName);
	}

	/**
	 * Function which will assign existing values to the roles
	 * @param \FreeCRM\Http\Vtiger_Request $request
	 */
	public function showAssignValueToRoleView(\FreeCRM\Http\Vtiger_Request $request)
	{
		$sourceModule = $request->get('source_module');
		$pickFieldId = $request->get('pickListFieldId');
		$fieldModel = \FreeCRM\Modules\Settings\Picklist\Models\Field::getInstance($pickFieldId);

		$moduleName = $request->getModule();
		$qualifiedName = $request->getModule(false);

		$selectedFieldAllPickListValues = \App\Fields\Picklist::getPickListValues($fieldModel->getName());
		$selectedFieldAllPickListValues = array_map('\FreeCRM\Modules\Vtiger\Util::toSafeHTML', $selectedFieldAllPickListValues);
		$viewer = $this->getViewer($request);
		$viewer->assign('SELECTED_PICKLIST_FIELDMODEL', $fieldModel);
		$viewer->assign('SELECTED_MODULE_NAME', $sourceModule);
		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('QUALIFIED_MODULE', $qualifiedName);
		$viewer->assign('ROLES_LIST', \FreeCRM\Modules\Settings\Roles\Models\Record::getAll());
		$viewer->assign('SELECTED_PICKLISTFIELD_ALL_VALUES', $selectedFieldAllPickListValues);
		$viewer->view('AssignValueToRole.tpl', $qualifiedName);
	}
}
