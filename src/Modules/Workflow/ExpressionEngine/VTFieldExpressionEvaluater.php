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
 * Expression evaluator for workflow field expressions
 */
class VTFieldExpressionEvaluater
{
	private $operators;
	private $functions;
	private $operations;
	private $expr;
	private $env;

	public function __construct($expr)
	{

		$this->operators = array(
			'+' => '__vt_add',
			'-' => '__vt_sub',
			'*' => '__vt_mul',
			'/' => '__vt_div',
			'==' => '__vt_equals',
			'<=' => '__vt_ltequals',
			'>=' => '__vt_gtequals',
			'<' => '__vt_lt',
			'>' => '__vt_gt',
		);
		$this->functions = array(
			'concat' => '__vt_concat',
			'time_diff' => '__vt_time_diff',
			'time_diffdays' => '__vt_time_diffdays',
			'add_days' => '__vt_add_days',
			'sub_days' => '__vt_sub_days',
			'get_date' => '__vt_get_date',
			'add_time' => '__vt_add_time',
			'sub_time' => '__vt_sub_time'
		);

		$this->operations = array_merge($this->functions, $this->operators);
		$this->expr = $expr;
	}

	function evaluate($env)
	{
		$this->env = $env;
		return $this->exec($this->expr);
	}

	function exec($expr)
	{
		if ($expr instanceof VTExpressionSymbol) {
			return $this->env($expr);
		} else if ($expr instanceof VTExpressionTreeNode) {
			$op = $expr->getName();
			if ($op->value == 'if') {
				$params = $expr->getParams();
				$cond = $this->exec($params[0]);
				if ($cond) {
					return $this->exec($params[1]);
				} else {
					return $this->exec($params[2]);
				}
			} else {
				$params = array_map(array($this, 'exec'), $expr->getParams());
				$func = $this->operations[$op->value];
				return $func($params);
			}
		} else {
			return $expr;
		}
	}

	function env($sym)
	{
		if ($this->env) {
			return $this->env->get($sym->value);
		} else {
			return $sym->value;
		}
	}
}