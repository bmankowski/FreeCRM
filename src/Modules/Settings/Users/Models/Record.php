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
}

