<?php

namespace App\Modules\com_vtiger_workflow\expression_engine;

class VTExpressionSpaceFilter
{

	function __construct($tokens)
	{
		$this->tokens = $tokens;
	}

	function nextToken()
	{
		do {
			$token = $this->tokens->nextToken();
		} while ($token->label == "SPACE");
		return $token;
	}
}