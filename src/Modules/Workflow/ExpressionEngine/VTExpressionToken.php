<?php

namespace App\Modules\Workflow\ExpressionEngine;

/* +**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * ********************************************************************************** */

/**
 * Expression token
 */
class VTExpressionToken
{
	public $label;
	public $value;

	public function __construct(string $label, $value = null)
	{
		$this->label = $label;
		$this->value = $value;
	}
}