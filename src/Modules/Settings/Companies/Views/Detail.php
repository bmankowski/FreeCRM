<?php

namespace FreeCRM\Modules\Settings\Companies\Views;



/**
 * Companies detail view class
 * @package YetiForce.Settings.View
 * @license licenses/License.html
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */

use FreeCRM\Modules\Settings\Companies\Models\Record as Settings_Companies_Record_Model;
class Detail extends \FreeCRM\Modules\Settings\Vtiger\Views\Index
{

	/**
	 * Process
	 * @param \FreeCRM\Http\Vtiger_Request $request
	 */
	public function process(\FreeCRM\Http\Vtiger_Request $request)
	{
		$record = $request->get('record');
		$qualifiedModuleName = $request->getModule(false);
		$recordModel = Settings_Companies_Record_Model::getInstance($record);

		$viewer = $this->getViewer($request);
		$viewer->assign('COMPANY_COLUMNS', Settings_Companies_Module_Model::getColumnNames());
		$viewer->assign('RECORD_MODEL', $recordModel);
		$viewer->assign('QUALIFIED_MODULE', $qualifiedModuleName);
		$viewer->assign('USER_MODEL', Users_Record_Model::getCurrentUserModel());
		$viewer->view('DetailView.tpl', $qualifiedModuleName);
	}
}
