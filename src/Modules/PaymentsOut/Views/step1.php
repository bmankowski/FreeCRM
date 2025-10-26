<?php
/* +***********************************************************************************************************************************
 * The contents of this file are subject to the YetiForce Public License Version 1.1 (the "License"); you may not use this file except
 * in compliance with the License.
 * Software distributed under the License is distributed on an "AS IS" basis, WITHOUT WARRANTY OF ANY KIND, either express or implied.
 * See the License for the specific language governing rights and limitations under the License.
 * The Original Code is YetiForce.
 * The Initial Developer of the Original Code is YetiForce. Portions created by YetiForce are Copyright (C) www.yetiforce.com.
 * All Rights Reserved.
 * *********************************************************************************************************************************** */

class PaymentsOut_step1_View extends \App\Modules\Base\Views\Index
{

	public function preProcess(\App\Http\Vtiger_Request $request)
	{
		parent::preProcess($request);
	}

	public function process(\App\Http\Vtiger_Request $request)
	{
		$moduleSettingsName = $request->getModule(false);
		$moduleName = $request->getModule();
		$paymentsOut = array();
		$record = \App\Modules\Base\Models\Record::getCleanInstance($moduleName);
		$type = $request->get('type');
		$bank = $request->get('bank');
		$fileInstance = \App\Fields\File::loadFromRequest($_FILES['file']);
		if (!$fileInstance->validate()) {
			return false;
		}
		$this->saveFile();
		$recordParse = $record->getSummary($type, $bank, $_FILES['file']['name']);

		// only incomming records (C)
		$i = 0;
		$j = array();
		foreach ($recordParse->operations as $transfers) {
			foreach ($transfers as $key => $value) {
				if ($key == 'indicator' && $value == 'D')
					$paymentsOut[] = $transfers;
				if ($key == 'third_letter_currency_code') {
					$j[] = $i;
				}
			}
			$i++;
		}

		$json = json_encode($paymentsOut);
		$viewer = $this->getViewer($request);
		$viewer->assign('MODULENAME', $moduleName);
		$viewer->assign('PAYMENTSOUT', $paymentsOut);
		$viewer->assign('COUNT', count($paymentsOut));
		$viewer->assign('JSON', $json);
		echo $viewer->view('step1.tpl', $moduleSettingsName, true);
	}

	public function saveFile()
	{
		$address = vglobal('cache_dir');
		$localisation = $address . $_FILES['file']['name'];
		if (is_uploaded_file($_FILES['file']['tmp_name'])) {
			if (!move_uploaded_file($_FILES['file']['tmp_name'], $localisation)) {
				return false;
			}
		} else {
			return false;
		}
		return true;
	}
}
