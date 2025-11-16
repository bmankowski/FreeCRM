<?php

namespace App\Modules\ModTracker\Models;

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */

class Field extends \App\Modules\Base\Models\Field
{
	protected $data = [];

	/**
	 * Function to set data
	 * @param array $values
	 * @return $this
	 */
	public function setData($values)
	{
		$this->data = $values;
		return $this;
	}

	/**
	 * Function to get data value
	 * @param string $key
	 * @return mixed
	 */
	public function get($key)
	{
		return isset($this->data[$key]) ? $this->data[$key] : null;
	}

	/**
	 * Function to set data value
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
	 * Function to check if key exists
	 * @param string $key
	 * @return bool
	 */
	public function has($key)
	{
		return isset($this->data[$key]);
	}

	/**
	 * Function to set parent to this model
	 * @param \App\Modules\Base\Models\Record
	 */
	public function setParent($parent)
	{
		$this->parent = $parent;
		return $this;
	}

	/**
	 * Function to get parent
	 * @return \App\Modules\Base\Models\Record
	 */
	public function getParent()
	{
		return $this->parent;
	}

	/**
	 * Function to set Field instance
	 * @param \App\Modules\Base\Models\Field
	 */
	public function setFieldInstance($fieldModel)
	{
		$this->fieldInstance = $fieldModel;
		return $this;
	}

	/**
	 * Function to get Field instance
	 * @return \App\Modules\Base\Models\Field
	 */
	public function getFieldInstance()
	{
		return $this->fieldInstance;
	}

	/**
	 * Function to get Old value of this Field
	 * @return string
	 */
	public function getOldValue()
	{
		$value = $this->getDisplayValue($this->get('prevalue'));
		if ($this->getFieldInstance()->getFieldDataType() != 'text') {
			return $value;
		}
		$teaser = \vtlib\Functions::textLength($value, \App\AppConfig::module('ModTracker', 'TEASER_TEXT_LENGTH'));
		if (substr($teaser, -3) == '...') {
			$value = \App\Security\Purifier::purify(\vtlib\Functions::removeHtmlTags(array('br', 'link', 'style', 'a', 'img', 'script', 'base'), $value));
			$this->set('fullPreValue', $value);
		}
		return $teaser;
	}

	/**
	 * Function to get new(updated) value of this Field
	 * @return string
	 */
	public function getNewValue()
	{
		$value = $this->getDisplayValue($this->get('postvalue'));
		if ($this->getFieldInstance()->getFieldDataType() != 'text') {
			return $value;
		}
		$teaser = \vtlib\Functions::textLength($value, \App\AppConfig::module('ModTracker', 'TEASER_TEXT_LENGTH'));
		if (substr($teaser, -3) == '...') {
			$value = \App\Security\Purifier::purify(\vtlib\Functions::removeHtmlTags(array('br', 'link', 'style', 'a', 'img', 'script', 'base'), $value));
			$this->set('fullPostValue', $value);
		}
		return $teaser;
	}

	/**
	 * Function to get name
	 * @return <type>
	 */
	public function getName()
	{
		return $this->getFieldInstance()->get('label');
	}

	/**
	 * Function to get Display Value
	 * @param <type> $value
	 * @return string
	 */
	public function getDisplayValue($value, $record = false, $recordInstance = false, $rawText = false)
	{
		return $this->getFieldInstance()->getDisplayValue($value, $record, $recordInstance, $rawText);
	}

	/**
	 * Function returns the module name of the field
	 * @return string|null
	 */
	public function getModuleName(): ?string
	{
		return $this->getParent()->getParent()->getModule()->getName();
	}
}
