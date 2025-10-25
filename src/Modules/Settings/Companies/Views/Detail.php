<?php

namespace App\Modules\Settings\Companies\Views;



/**
 * Companies detail view class
 * @package YetiForce.Settings.View
 * @license licenses/License.html
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */

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
		$recordModel = \App\Modules\Settings\Companies\Models\Record::getInstance($record);

		$viewer = $this->getViewer($request);
		$viewer->assign('COMPANY_COLUMNS', \App\Modules\Settings\Companies\Models\Module::getColumnNames());
		$viewer->assign('RECORD_MODEL', $recordModel);
		$viewer->assign('QUALIFIED_MODULE', $qualifiedModuleName);
		$viewer->assign('USER_MODEL', $request->getUser());
		$viewer->view('DetailView.tpl', $qualifiedModuleName);
	}
}
