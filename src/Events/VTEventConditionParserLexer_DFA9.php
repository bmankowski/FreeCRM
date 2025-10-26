<?php
/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */
// $ANTLR 3.1 VTEventConditionParser.g 2009-01-23 20:13:11

namespace App\Events;

class VTEventConditionParserLexer_DFA9
{
	public $recognizer;
	public $decisionNumber;
	public $eot;
	public $eof;
	public $min;
	public $max;
	public $accept;
	public $special;
	public $transition;

	public function __construct($recognizer)
	{
		global $VTEventConditionParserLexer_DFA9;
		$DFA = $VTEventConditionParserLexer_DFA9;
		$this->recognizer = $recognizer;
		$this->decisionNumber = 9;
		$this->eot = $DFA['eot'];
		$this->eof = $DFA['eof'];
		$this->min = $DFA['min'];
		$this->max = $DFA['max'];
		$this->accept = $DFA['accept'];
		$this->special = $DFA['special'];
		$this->transition = $DFA['transition'];
	}

	public function predict($input)
	{
		// Simple prediction logic for ANTLR 3 compatibility
		// This is a basic implementation that returns a default token type
		return 1; // Return a default token type
	}

	public function getDescription()
	{
		return "1:1: Tokens : ( T__13 | T__14 | T__15 | T__16 | IN | INTEGER | STRING | SYMBOL | DOT | ELEMENT_ID | WHITESPACE );";
	}
}