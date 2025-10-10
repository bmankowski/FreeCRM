<?php
/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */

namespace FreeCRM\Runtime;

/**
 * JavaScript Script Model - extends Vtiger_JavaScript with model functionality
 */

use FreeCRM\Modules\Vtiger\Models\JsScript;
class Vtiger_JsScript_Model extends JsScript
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
	 * Get the source URL
	 * @return string
	 */
	public function getSrc()
	{
		return $this->get('src');
	}
	
	/**
	 * Get the script type
	 * @return string
	 */
	public function getType()
	{
		return $this->get('type') ?? 'text/javascript';
	}
}
