<?php

namespace App\Modules\com_vtiger_workflow;

/**
 * This is a simple parser for conditional expressions used to trigger workflow actions.
 * 
 */
class VTConditionalParser
{

	public function __construct($expr)
	{
		$this->tokens = $this->getTokens($expr);
		$this->pos = 0;
	}

	private function getTokens($expression)
	{
		preg_match_all('/and|or|\\d+|=|\\w+|\\(|\\)/', $expression, $matches, PREG_SET_ORDER);
		$tokens = array();
		foreach ($matches as $arr) {
			$tokenVal = $arr[0];
			if (in_array($tokenVal, array("and", "or", "=", "(", ")"))) {
				$tokenType = "op";
			} else if (is_numeric($tokenVal)) {
				$tokenType = "num";
			} else {
				$tokenType = "sym";
			}
			$tokens[] = array($tokenType, $tokenVal);
		}
		return $tokens;
	}

	public function parse()
	{
		$op = array(
			"and" => array("op", "and"),
			"or" => array("op", "or"),
			"=" => array("op", "="),
			"(" => array("op", "("),
			")" => array("op", ")"));

		if ($this->peek() == $op['(']) {
			$this->nextToken();
			$left = $this->parse();
			if ($this->nextToken() != $op[')']) {
				throw new VTParseFailed();
			}
		} else {
			$left = $this->cond();
		}
		if (sizeof($this->tokens) > $this->pos && in_array($this->peek(), array($op["and"], $op["or"]))) {
			$nt = $this->nextToken();
			return array($nt[1], $left, $this->parse());
		} else {
			return $left;
		}
	}

	private function cond()
	{
		$left = $this->nextToken();
		$operator = $this->nextToken();
		$right = $this->nextToken();
		return array($operator[1], $left, $right);
	}

	private function peek()
	{
		return $this->tokens[$this->pos];
	}

	private function nextToken()
	{
		$this->pos+=1;
		return $this->tokens[$this->pos - 1];
	}
}