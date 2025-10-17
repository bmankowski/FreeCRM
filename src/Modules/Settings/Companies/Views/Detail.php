<?php

namespace App\Modules\Settings\Companies\Views;



/**
 * Companies detail view class
 * @package YetiForce.Settings.View
 * @license licenses/License.html
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */

use App\Modules\Settings\Companies\Models\Record as Settings_Companies_Record_Model;
class Detail extends \App\Modules\Settings\Vtiger\Views\Index
{

	/**
	 * Process
	 * @param \App\Http\Vtiger_Request $request
	 */
	public function process(\App\Http\Vtiger_Request $request)
	{
		$record = $request->get('record');
		$qualifiedModuleName = $request->getModule(false);
		$recordModel = Settings_Companies_Record_Model::getInstance($record);

		$viewer = $this->getViewer($request);
		$viewer->assign('COMPANY_COLUMNS', \App\Modules\Settings\Companies\Models\Module::getColumnNames());
		$viewer->assign('RECORD_MODEL', $recordModel);
		$viewer->assign('QUALIFIED_MODULE', $qualifiedModuleName);
		$viewer->assign('USER_MODEL', \App\Modules\Users\Models\Record::getCurrentUserModel());
		$viewer->view('DetailView.tpl', $qualifiedModuleName);
	}
}
