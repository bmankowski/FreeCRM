<?php

namespace App\Modules\Settings\Password\Models;


/* +***********************************************************************************************************************************
 * The contents of this file are subject to the YetiForce Public License Version 1.1 (the "License"); you may not use this file except
 * in compliance with the License.
 * Software distributed under the License is distributed on an "AS IS" basis, WITHOUT WARRANTY OF ANY KIND, either express or implied.
 * See the License for the specific language governing rights and limitations under the License.
 * The Original Code is YetiForce.
 * The Initial Developer of the Original Code is YetiForce. Portions created by YetiForce are Copyright (C) www.yetiforce.com. 
 * All Rights Reserved.
 * *********************************************************************************************************************************** */

class Record extends \App\Modules\Base\Models\Record
{

	public static function getPassDetail($type = false)
	{
		$query = (new \App\Db\Query())->from('vtiger_password');
		if ($type) {
			$query->where(['type' => $type]);
		}
		$dataReader = $query->createCommand()->query();
		while($row = $dataReader->read()) {
			$resp[$row['type']] = $row['val'];
		}
		return $resp;
	}

	public static function setPassDetail($type, $vale)
	{
		\App\Db\Db::getInstance()->createCommand()
			->update('vtiger_password', ['val' => $vale], ['type' => $type])
			->execute();
	}

	public static function validation($type, $vale)
	{
		if ($type == 'min_length' || $type == 'max_length') {
			return is_numeric($vale);
		}
		if ($type == 'big_letters' || $type == 'small_letters' || $type == 'numbers' || $type == 'special') {
			if ($vale === 'false' || $vale === 'true') {
				return true;
			} else {
				return false;
			}
		}
	}

	public static function checkPassword($pass)
	{
		$conf = self::getPassDetail();
		$moduleName = 'Settings:Password';
		if (strlen($pass) > $conf['max_length']) {
			return \App\Runtime\Vtiger_Language_Handler::translate("Maximum password length", $moduleName) . ' ' . $conf['max_length'] . ' ' . \App\Runtime\Vtiger_Language_Handler::translate("characters", $moduleName);
		}
		if (strlen($pass) < $conf['min_length']) {
			return \App\Runtime\Vtiger_Language_Handler::translate("Minimum password length", $moduleName) . ' ' . $conf['min_length'] . ' ' . \App\Runtime\Vtiger_Language_Handler::translate("characters", $moduleName);
		}
		if ($conf['numbers'] == 'true' && !preg_match("#[0-9]+#", $pass)) {
			return \App\Runtime\Vtiger_Language_Handler::translate("Password should contain numbers", $moduleName);
		}
		if ($conf['big_letters'] == 'true' && !preg_match("#[A-Z]+#", $pass)) {
			return \App\Runtime\Vtiger_Language_Handler::translate("Uppercase letters from A to Z", $moduleName);
		}
		if ($conf['small_letters'] == 'true' && !preg_match("#[a-z]+#", $pass)) {
			return \App\Runtime\Vtiger_Language_Handler::translate("Lowercase letters a to z", $moduleName);
		}
		if ($conf['special'] == 'true' && !preg_match("/[!@#$%^&*()\-_=+{};:,<.>]/", $pass)) {
			return \App\Runtime\Vtiger_Language_Handler::translate("Password should contain special characters", $moduleName);
		}
		return false;
	}
}
