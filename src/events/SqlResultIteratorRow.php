<?php
/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */

namespace App\Events;

class SqlResultIteratorRow
{

    /** @var array */
    public $data;


	function __construct($data)
	{
		$this->data = $data;
	}

	function get($column)
	{
		return $this->data[$column];
	}

	function __get($column)
	{
		return $this->get($column);
	}
}