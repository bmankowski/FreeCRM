<?php

namespace App\Modules\Workflow;

/* +**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * ********************************************************************************** */

/**
 * Abstract base class for workflow tasks
 */
abstract class VTTask
{
	public $contents;

	/**
	 * Execute the task
	 */
	abstract public function doTask($recordModel);

	/**
	 * Get field names for this task
	 */
	abstract public function getFieldNames(): array;

	/**
	 * Get time field list
	 */
	public function getTimeFieldList(): array
	{
		return [];
	}

	/**
	 * Get task contents
	 */
	public function getContents($recordModel)
	{
		return $this->contents;
	}

	/**
	 * Set task contents
	 */
	public function setContents($recordModel): void
	{
		$this->contents = $recordModel;
	}

	/**
	 * Check if task has contents
	 */
	public function hasContents($recordModel): bool
	{
		return !empty($this->getContents($recordModel));
	}

	/**
	 * Format time for time picker
	 */
	public function formatTimeForTimePicker(string $time): string
	{
		list($h, $m, $s) = explode(':', $time);
		$mn = str_pad($m - $m % 15, 2, 0, STR_PAD_LEFT);
		$AM_PM = ['am', 'pm'];
		return str_pad(($h % 12), 2, 0, STR_PAD_LEFT) . ':' . $mn . $AM_PM[($h / 12) % 2];
	}
}

