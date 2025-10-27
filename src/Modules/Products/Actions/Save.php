<?php

namespace App\Modules\Products\Actions;

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */

class Save extends \App\Base\Controllers\BaseActionController
{

	public function process(\App\Http\Vtiger_Request $request)
	{
		$result = \App\Modules\Base\Helpers\Util::transformUploadedFiles($_FILES, true);
		$_FILES = $result['imagename'];
		parent::process($request);
	}
}
