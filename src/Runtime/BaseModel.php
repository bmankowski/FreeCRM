<?php
/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */

/**
 * Base Model Class
 */

namespace App\Runtime;

class BaseModel
{

	protected $valueMap;

	/**
	 * Constructor
	 * @param array $values
	 */
	public function __construct($values = [])
	{
		$this->valueMap = $values;
	}

	/**
	 * Function to get the value for a given key
	 * @param $key
	 * @return Value for the given key
	 */
	public function get($key)
	{
		return isset($this->valueMap[$key]) ? $this->valueMap[$key] : null;
	}

	/**
	 * Function to get the value if its safe to use for SQL Query (column).
	 * @param string $key
	 * @param boolean $skipEmpty - Skip the check if string is empty
	 * @return Value for the given key
	 */
	public function getForSql($key, $skipEmtpy = true)
	{
		return \App\Purifier::purifySql($this->get($key), $skipEmtpy);
	}

	/**
	 * Function to set the value for a given key
	 * @param $key
	 * @param $value
	 * @return BaseModel
	 */
	public function set($key, $value)
	{
		$this->valueMap[$key] = $value;
		return $this;
	}

	/**
	 * Function to set all the values for the Object
	 * @param array (key-value mapping) $values
	 * @return BaseModel
	 */
	public function setData($values)
	{
		$this->valueMap = $values;
		return $this;
	}

	/**
	 * Function to get all the values of the Object
	 * @return array (key-value mapping)
	 */
	public function getData()
	{
		return $this->valueMap;
	}

	/**
	 * Function to check if the key exists.
	 * @param string $key
	 */
	public function has($key)
	{
		return isset($this->valueMap[$key]);
	}

	/**
	 * Function to check if the key is empty.
	 * @param type $key
	 */
	public function isEmpty($key)
	{
		if(empty($this->valueMap)) {
			return true;
		}
		return (!isset($this->valueMap[$key]) || $this->valueMap[$key] === '');
	}

	/**
	 * Function to remove the value
	 * @param type $key
	 */
	public function remove($key)
	{
		unset($this->valueMap[$key]);
	}

	/**
	 * Function to get keys
	 */
	public function getKeys()
	{
		return array_keys($this->valueMap);
	}

	/**
	 * Function to get the html value for a given key
	 * @param string $key
	 * @return mixed
	 */
	public function getHtmlEncode($key)
	{
		return \App\Purifier::encodeHtml($this->get($key));
	}
}
