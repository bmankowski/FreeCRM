<?php

namespace App\Modules\Base\Views;

/**
 * EditFieldByModal View Class
 * @package YetiForce.View
 * @license licenses/License.html
 * @author Radosław Skrzypczak <r.skrzypczak@yetiforce.com>
 */

use App\Http\Vtiger_Request;

class EditFieldByModal  extends \App\Modules\Base\Views\Index
{

	protected $showFields = [];
	protected $restrictItems = [];

	public function checkPermission(\App\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$recordId = $request->get('record');

		$recordPermission = \App\Modules\Users\Models\Privileges::isPermitted($moduleName, 'Save', $recordId);
		if (!$recordPermission) {
			throw new \App\Exceptions\NoPermittedToRecord('LBL_NO_PERMISSIONS_FOR_THE_RECORD');
		}
		return true;
	}

	public function process(\App\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$ID = $request->get('record');

		$recordModel = \App\Modules\Base\Models\DetailView::getInstance($moduleName, $ID)->getRecord();
		$recordStrucure = \App\Modules\Base\Models\RecordStructure::getInstanceFromRecordModel($recordModel, \App\Modules\Base\Models\RecordStructure::RECORD_STRUCTURE_MODE_SUMMARY);
		$structuredValues = $recordStrucure->getStructure();

		$viewer = $this->getViewer($request);
		$viewer->assign('MODULE_NAME', $moduleName);
		$viewer->assign('RECORD', $recordModel);
		$viewer->assign('SHOW_FIELDS', $this->getFieldsToShow());
		$viewer->assign('RECORD_STRUCTURE', $structuredValues);
		$viewer->assign('RESTRICTS_ITEM', $this->getRestrictItems());
		$viewer->assign('CONDITION_TO_RESTRICTS', $this->getConditionToRestricts($moduleName, $ID));
		$this->preProcess($request);
		$viewer->view('EditFieldByModal.tpl', $moduleName);
		$this->postProcess($request);
	}

	public function getFieldsToShow()
	{
		return $this->showFields;
	}

	public function getRestrictItems()
	{
		return $this->restrictItems;
	}

	public function getConditionToRestricts($moduleName, $ID)
	{
		return true;
	}
}
