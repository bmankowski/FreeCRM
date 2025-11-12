<?php

namespace App\Modules\Settings\Users\Models;

/**
 * Settings Users Record Model Class
 */
class Record extends \App\Modules\Settings\Base\Models\Record
{
	/** @var \App\Modules\Users\Models\Record */
	protected $userModel;

	/**
	 * Function to get the Id
	 * @return int User Id
	 */
	public function getId()
	{
		return $this->get('id');
	}

	/**
	 * Function to get the User Name
	 * @return string
	 */
	public function getName()
	{
		$firstName = $this->get('first_name');
		$lastName = $this->get('last_name');
		return trim("$firstName $lastName");
	}

	/**
	 * Function to get instance
	 * @param int $id - User ID
	 * @return \App\Modules\Settings\Users\Models\Record
	 */
	public static function getInstanceById($id)
	{
		$instance = new self();
		$userModel = \App\Modules\Users\Models\Record::getInstanceById($id, 'Users');
		$instance->setData($userModel->getData());
		$instance->userModel = $userModel;
		return $instance;
	}

	/**
	 * Function to get the underlying user model
	 * @return \App\Modules\Users\Models\Record
	 */
	public function getUserModel()
	{
		if (!$this->userModel) {
			$this->userModel = \App\Modules\Users\Models\Record::getInstanceById($this->getId(), 'Users');
		}
		return $this->userModel;
	}

	/**
	 * Function to get the list view actions for the record
	 * @return array - Array of \App\Modules\Base\Models\Link instances
	 */
	public function getRecordLinks()
	{
		$links = [];
		
		$recordLinks = [
			[
				'linktype' => 'LISTVIEWRECORD',
				'linklabel' => 'LBL_EDIT_RECORD',
				'linkurl' => 'index.php?module=Users&parent=Settings&view=Edit&record=' . $this->getId(),
				'linkicon' => 'glyphicon glyphicon-pencil'
			],
		];

		$currentUser = \App\Modules\Users\Models\Record::getCurrentUserModel();
		$canChangePassword = $currentUser && ($currentUser->isAdminUser() || $currentUser->getId() == $this->getId());
		if ($canChangePassword) {
			$recordLinks[] = [
				'linktype' => 'LISTVIEWRECORD',
				'linklabel' => 'LBL_CHANGE_PASSWORD',
				'linkurl' => "javascript:Settings_Users_ListView_Js.triggerChangePassword('index.php?module=Users&view=EditAjax&mode=changePassword&record=" . $this->getId() . "','Users')",
				'linkicon' => 'glyphicon glyphicon-lock',
			];
		}

		$recordLinks[] = [
			'linktype' => 'LISTVIEWRECORD',
			'linklabel' => 'LBL_DELETE_RECORD',
			'linkurl' => 'index.php?module=Users&parent=Settings&action=DeleteUser&record=' . $this->getId(),
			'linkicon' => 'glyphicon glyphicon-trash'
		];
		
		foreach ($recordLinks as $recordLink) {
			$links[] = \App\Modules\Base\Models\Link::getInstanceFromValues($recordLink);
		}
		
		return $links;
	}

	/**
	 * Function to get the display value
	 * @param string $key
	 * @return mixed
	 */
	public function getDisplayValue($key)
	{
		$value = $this->get($key);
		
		// Handle is_admin field
		if ($key === 'is_admin') {
			if ($value === 'on' || $value === 1 || $value === '1' || $value === true) {
				return \App\Runtime\Vtiger_Language_Handler::translate('LBL_YES', 'Users');
			}
			return \App\Runtime\Vtiger_Language_Handler::translate('LBL_NO', 'Users');
		}
		
		// Handle status field  
		if ($key === 'status') {
			return \App\Runtime\Vtiger_Language_Handler::translate($value, 'Users');
		}
		
		return $value;
	}
}

