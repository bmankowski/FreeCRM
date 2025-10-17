<?php

namespace App\Modules\Vtiger;

/**
 * Updater Field Class
 * @package YetiForce.Helpers
 * @license licenses/License.html
 * @author Tomasz Kur <t.kur@yetiforce.com>
 */
class UpdaterField {

	private $fieldModel = false;

	/**
	 * Function to get instance of class
	 * @return \self
	 */
	public static function getInstance()
	{
		return new self;
	}

	/**
	 * Function to set field model
	 * @param \App\Modules\Vtiger\Models\Field $fieldModel
	 */
	public function setFieldModel(\App\Modules\Vtiger\Models\Field $fieldModel)
	{
		$this->fieldModel = $fieldModel;
	}

	/**
	 * Function to get value for field
	 * @return mixed
	 * @throws Exception\NotAllowedMethod
	 */
	public function getValue()
	{
		$fieldName = $this->fieldModel->getFieldName();
		$functionName = 'get' . ucwords($fieldName) . 'Value';
		if (!method_exists($this, $functionName)) {
			throw new Exception\NotAllowedMethod();
		}
		return $this->$functionName();
	}
}
