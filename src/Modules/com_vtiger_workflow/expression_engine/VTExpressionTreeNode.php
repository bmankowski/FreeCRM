<?php

namespace App\Modules\com_vtiger_workflow\expression_engine;

 * **************************************************************************** */

class VTExpressionTreeNode {

	function __construct($arr)
	{
		$this->arr = $arr;
	}

	function getParams()
	{
		$arr = $this->arr;
		return array_slice($arr, 1, sizeof($arr) - 1);
	}

	function getName()
	{
		return $this->arr[0];
	}
}