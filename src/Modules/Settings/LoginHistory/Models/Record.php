<?php

namespace App\Modules\Settings\LoginHistory\Models;



/**
 * 
 * @package YetiForce.Models
 * @license licenses/License.html
 * @author Mriusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */
class Record extends \App\Modules\Settings\Base\Models\Record
{

	/**
	 * Function to get the Id
	 * @return mixed Profile Id
	 */
	public function getId()
	{
		return $this->get('login_id');
	}

	/**
	 * Function to get the Profile Name
	 * @return string
	 */
	public function getName()
	{
		return $this->get('user_name');
	}

	public function getAccessibleUsers()
	{
		$usersListArray = [];
		$dataReader = (new \App\Db\Query())->select('user_name')
				->from('vtiger_users')
				->createCommand()->query();
		while ($userName = $dataReader->readColumn(0)) {
			$usersListArray[$userName] = $userName;
		}
		return $usersListArray;
	}

	/**
	 * Function to retieve display value for a field
	 * @param string $fieldName - field name for which values need to get
	 * @return string
	 */
	public function getDisplayValue(string $fieldName, $recordId = false): string
	{
		switch ($fieldName) {
			case 'login_time':
			case 'logout_time':
				if ($this->get($fieldName) !== '0000-00-00 00:00:00') {
					return \App\Modules\Base\UiTypes\Datetime::getDateTimeValue($this->get($fieldName));
				} else {
					return '---';
				}
			case 'user_name':
				return $this->getHtmlEncode($fieldName);
			case 'status':
				return \App\Runtime\Vtiger_Language_Handler::translate($this->get($fieldName), 'Settings::Vtiger');
			default:
				return (string) ($this->get($fieldName) ?? '');
		}
	}
}
