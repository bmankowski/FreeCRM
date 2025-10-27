<?php
namespace App\View\Assets;

/**
 * Base Asset - Abstract base class for asset data models
 */
abstract class BaseAsset
{
	protected $data = [];

	/**
	 * Set a property on the model and return this for chaining
	 * @param string $key
	 * @param mixed $value
	 * @return $this
	 */
	public function set($key, $value)
	{
		$this->data[$key] = $value;
		return $this;
	}

	/**
	 * Get a property from the model
	 * @param string $key
	 * @return mixed
	 */
	public function get($key)
	{
		return $this->data[$key] ?? null;
	}

	/**
	 * Function to get the instance of Asset model from a given \vtlib\Link object
	 * @param \vtlib\Link $linkObj
	 * @return static
	 */
	public static function getInstanceFromLinkObject(\vtlib\Link $linkObj)
	{
		$objectProperties = get_object_vars($linkObj);
		$linkModel = new static();
		foreach ($objectProperties as $properName => $propertyValue) {
			$linkModel->$properName = $propertyValue;
		}
		return $linkModel->setData($objectProperties);
	}

	/**
	 * Set data properties
	 * @param array $data
	 * @return $this
	 */
	protected function setData(array $data)
	{
		foreach ($data as $key => $value) {
			$this->data[$key] = $value;
		}
		return $this;
	}
}

