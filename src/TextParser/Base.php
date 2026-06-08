<?php

namespace App\TextParser;

class Base
{
	public $name = '';

	public $allowedModules;

	public $textParser;

	public $params;

	public $type;

	public function __construct(\App\TextParser\TextParser $textParser, $params = '')
	{
		$this->textParser = $textParser;
		$this->params = $params;
	}

	public function isActive()
	{
		if (isset($this->textParser->moduleName) && isset($this->allowedModules) && !in_array($this->textParser->moduleName, $this->allowedModules)) {
			return false;
		}
		if (isset($this->textParser->type) && $this->textParser->type !== $this->type) {
			return false;
		}
		return true;
	}
}
