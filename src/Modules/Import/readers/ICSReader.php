<?php

namespace FreeCRM\Modules\Import\readers;

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */

require_once ROOT_DIRECTORY . '/modules/Calendar/iCal/ical-parser-class.php';

class ICSReader extends iCal
{

	/**
	 * Function to get info about imported file contains header or not
	 * @return <boolean>
	 */
	public function hasHeader()
	{
		return true;
	}

	/**
	 * Function to get info about imported file contains First Row or not
	 * @param <boolean> $hasHeader
	 * @return <boolean>
	 */
	public function getFirstRowData($hasHeader = true)
	{
		return true;
	}
}

?>
