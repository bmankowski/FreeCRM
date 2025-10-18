<?php

namespace App\Modules\SMSNotifier;

class SMSNotifierManager
{

	/** Server configuration management */
	static function listAvailableProviders()
	{
		return SMSNotifier_Provider_Model::listAll();
	}

	static function getActiveProviderInstance()
	{
		$adb = \App\Database\PearDatabase::getInstance();
		$result = $adb->pquery("SELECT * FROM vtiger_smsnotifier_servers WHERE isactive = 1 LIMIT 1", array());
		if ($result && $adb->num_rows($result)) {
			$resultrow = $adb->fetch_array($result);
			$provider = SMSNotifier_Provider_Model::getInstance($resultrow['providertype']);
			$parameters = array();
			if (!empty($resultrow['parameters']))
				$parameters = \App\Json::decode(\App\Utils\ListViewUtils::decodeHtml($resultrow['parameters']));
			foreach ($parameters as $k => $v) {
				$provider->setParameter($k, $v);
			}
			$provider->setAuthParameters($resultrow['username'], $resultrow['password']);

			return $provider;
		}
		return false;
	}

	static function listConfiguredServer($id)
	{
		$adb = \App\Database\PearDatabase::getInstance();
		$result = $adb->pquery("SELECT * FROM vtiger_smsnotifier_servers WHERE id=?", array($id));
		if ($result) {
			return $adb->fetchByAssoc($result);
		}
		return false;
	}

	static function listConfiguredServers()
	{
		$adb = \App\Database\PearDatabase::getInstance();
		$result = $adb->pquery("SELECT * FROM vtiger_smsnotifier_servers", array());
		$servers = array();
		if ($result) {
			while ($row = $adb->fetchByAssoc($result)) {
				$servers[] = $row;
			}
		}
		return $servers;
	}

	static function updateConfiguredServer($id, $frmvalues)
	{
		$adb = \App\Database\PearDatabase::getInstance();
		$providertype = \App\Purifier::purify($frmvalues['smsserver_provider']);
		$username = \App\Purifier::purify($frmvalues['smsserver_username']);
		$password = \App\Purifier::purify($frmvalues['smsserver_password']);
		$isactive = \App\Purifier::purify($frmvalues['smsserver_isactive']);

		$provider = SMSNotifier_Provider_Model::getInstance($providertype);

		$parameters = '';
		if ($provider) {
			$providerParameters = $provider->getRequiredParams();
			$inputServerParams = array();
			foreach ($providerParameters as $k => $v) {
				$lookupkey = "smsserverparam_{$providertype}_{$v}";
				if (isset($frmvalues[$lookupkey])) {
					$inputServerParams[$v] = \App\Purifier::purify($frmvalues[$lookupkey]);
				}
			}
			$parameters = \App\Json::encode($inputServerParams);
		}

		if (empty($id)) {
			$adb->pquery("INSERT INTO vtiger_smsnotifier_servers (providertype,username,password,isactive,parameters) VALUES(?,?,?,?,?)", array($providertype, $username, $password, $isactive, $parameters));
		} else {
			$adb->pquery("UPDATE vtiger_smsnotifier_servers SET username=?, password=?, isactive=?, providertype=?, parameters=? WHERE id=?", array($username, $password, $isactive, $providertype, $parameters, $id));
		}
	}

	static function deleteConfiguredServer($id)
	{
		$adb = \App\Database\PearDatabase::getInstance();
		$adb->pquery("DELETE FROM vtiger_smsnotifier_servers WHERE id=?", array($id));
	}
}