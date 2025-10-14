<?php

namespace FreeCRM\Modules\Settings\Currency\Views;


/* +**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * ********************************************************************************** */

use FreeCRM\Modules\Settings\Currency\Models\Record as Settings_Currency_Record_Model;
class TransformEditAjax extends \FreeCRM\Modules\Settings\Vtiger\Views\IndexAjax
{

	public function process(\FreeCRM\Http\Vtiger_Request $request)
	{
		$record = $request->get('record');

		$currencyList = Settings_Currency_Record_Model::getAll($record);

		$qualifiedName = $request->getModule(false);
		$viewer = $this->getViewer($request);
		$viewer->assign('QUALIFIED_MODULE', $qualifiedName);
		$viewer->assign('CURRENCY_LIST', $currencyList);
		$viewer->assign('RECORD_MODEL', Settings_Currency_Record_Model::getInstance($record));
		echo $viewer->view('TransformEdit.tpl', $qualifiedName, true);
	}
}
