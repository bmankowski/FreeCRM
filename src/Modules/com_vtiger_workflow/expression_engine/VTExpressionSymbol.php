<?php

namespace App\Modules\com_vtiger_workflow\expression_engine;

class VTExpressionSymbol
{

	function __construct($value)
	{
		$this->value = $value;
	}

	function __toString()
	{
		return "VTExpressionSymbol({$this->value})";
	}
}