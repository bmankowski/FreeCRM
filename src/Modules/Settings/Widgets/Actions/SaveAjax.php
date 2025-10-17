<?php

namespace FreeCRM\Modules\Settings\Widgets\Actions;
use FreeCRM\Modules\Settings\Widgets\Models\Module;


/* +***********************************************************************************************************************************
 * The contents of this file are subject to the YetiForce Public License Version 1.1 (the "License"); you may not use this file except
 * in compliance with the License.
 * Software distributed under the License is distributed on an "AS IS" basis, WITHOUT WARRANTY OF ANY KIND, either express or implied.
 * See the License for the specific language governing rights and limitations under the License.
 * The Original Code is YetiForce.
 * The Initial Developer of the Original Code is YetiForce. Portions created by YetiForce are Copyright (C) www.yetiforce.com. 
 * All Rights Reserved.
 * *********************************************************************************************************************************** */

class SaveAjax extends \FreeCRM\Modules\Settings\Vtiger\Views\IndexAjax
{

	public function __construct()
	{
		parent::__construct();
		$this->exposeMethod('saveWidget');
		$this->exposeMethod('removeWidget');
		$this->exposeMethod('updateSequence');
	}

	public function saveWidget(\FreeCRM\Http\Vtiger_Request $request)
	{
		$params = $request->get('params');
		\FreeCRM\Modules\Settings\Widgets\Models\Module::saveWidget($params);
		$response = new \FreeCRM\Http\Vtiger_Response();
		$response->setResult(array(
			'success' => 1,
			'message' => \FreeCRM\Runtime\Vtiger_Language_Handler::translate('Saved changes', $request->getModule(false))
		));
		$response->emit();
	}

	public function removeWidget(\FreeCRM\Http\Vtiger_Request $request)
	{
		$params = $request->get('params');
		\FreeCRM\Modules\Settings\Widgets\Models\Module::removeWidget($params['wid']);
		$response = new \FreeCRM\Http\Vtiger_Response();
		$response->setResult(array(
			'success' => 1,
			'message' => \FreeCRM\Runtime\Vtiger_Language_Handler::translate('Removed widget', $request->getModule(false))
		));
		$response->emit();
	}

	public function updateSequence(\FreeCRM\Http\Vtiger_Request $request)
	{
		$params = $request->get('params');
		\FreeCRM\Modules\Settings\Widgets\Models\Module::updateSequence($params);
		$response = new \FreeCRM\Http\Vtiger_Response();
		$response->setResult(array(
			'success' => 1,
			'message' => \FreeCRM\Runtime\Vtiger_Language_Handler::translate('Update has been completed', $request->getModule(false))
		));
		$response->emit();
	}
}
