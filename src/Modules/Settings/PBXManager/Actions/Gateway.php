<?php

namespace FreeCRM\Modules\Settings\PBXManager\Actions;


/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */

class Gateway extends \FreeCRM\Modules\Settings\Vtiger\Views\IndexAjax
{

	public function __construct()
	{
		$this->exposeMethod('getSecretKey');
	}

	public function process(\FreeCRM\Http\Vtiger_Request $request)
	{
		$this->getSecretKey($request);
	}

	public function getSecretKey(\FreeCRM\Http\Vtiger_Request $request)
	{
		$serverModel = PBXManager_Server_Model::getInstance();
		$response = new \FreeCRM\Http\Vtiger_Response();
		$vtigersecretkey = $serverModel->get('vtigersecretkey');
		if ($vtigersecretkey) {
			$connector = $serverModel->getConnector();
			$vtigersecretkey = $connector->getVtigerSecretKey();
			$response->setResult($vtigersecretkey);
		} else {
			$vtigersecretkey = PBXManager_Server_Model::generateVtigerSecretKey();
			$response->setResult($vtigersecretkey);
		}
		$response->emit();
	}
}
