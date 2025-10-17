<?php

namespace App\Modules\com_vtiger_workflow;

/* +**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * ********************************************************************************** */

abstract class VTTask
{

	var $contents;

	public abstract function doTask($recordModel);

	public abstract function getFieldNames();

	public function getTimeFieldList()
	{
		return array();
	}

	public function getContents($recordModel)
	{
		return $this->contents;
	}

	public function setContents($recordModel)
	{
		$this->contents = $recordModel;
	}

	public function hasContents($recordModel)
	{
		$taskContents = $this->getContents($recordModel);
		if ($taskContents) {
			return true;
		}
		return false;
	}

	public function formatTimeForTimePicker($time)
	{
		list($h, $m, $s) = explode(':', $time);
		$mn = str_pad($m - $m % 15, 2, 0, STR_PAD_LEFT);
		$AM_PM = array('am', 'pm');
		return str_pad(($h % 12), 2, 0, STR_PAD_LEFT) . ':' . $mn . $AM_PM[($h / 12) % 2];
	}
}

