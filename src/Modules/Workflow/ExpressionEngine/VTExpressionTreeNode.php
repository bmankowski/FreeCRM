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
 * Expression tree node for parsed expressions
 */
class VTExpressionTreeNode
{
	private $arr;

	public function __construct(array $arr)
	{
		$this->arr = $arr;
	}

	public function getParams(): array
	{
		return array_slice($this->arr, 1);
	}

	public function getName()
	{
		return $this->arr[0];
	}
}