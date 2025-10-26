<?php

namespace App\Modules\OSSMailView\Views;

/* +***********************************************************************************************************************************
 * The contents of this file are subject to the YetiForce Public License Version 1.1 (the "License"); you may not use this file except
 * in compliance with the License.
 * Software distributed under the License is distributed on an "AS IS" basis, WITHOUT WARRANTY OF ANY KIND, either express or implied.
 * See the License for the specific language governing rights and limitations under the License.
 * The Original Code is YetiForce.
 * The Initial Developer of the Original Code is YetiForce. Portions created by YetiForce are Copyright (C) www.yetiforce.com. 
 * All Rights Reserved.
 * *********************************************************************************************************************************** */


use App\Http\Vtiger_Request;
class Detail  extends \App\Modules\Base\Views\Detail
{

	public function __construct()
	{
		parent::__construct();
		$this->exposeMethod('showSummary');
	}

	public function isAjaxEnabled($recordModel)
	{
		return false;
	}

	public function showSummary(\App\Http\Vtiger_Request $request)
	{
		$record = $request->get('record');
		$viewer = $this->getViewer($request);
		$viewer->assign('RECORD', $record);
	}
}

?>
