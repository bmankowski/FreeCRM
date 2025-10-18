<?php
/* Driver template for the PHP_VTQL_ParserrGenerator parser generator. (PHP port of LEMON)
 */
/**
 * This can be used to store both the string representation of
 * a token, and any useful meta-data associated with the token.
 *
 * meta-data should be stored as an array
 */

namespace App\Webservices;

class VTQL_ParseryyToken implements ArrayAccess
{

	public $string = '';
	public $metadata = [];

	public function __construct($s, $m = [])
	{
		if ($s instanceof VTQL_ParseryyToken) {
			$this->string = $s->string;
			$this->metadata = $s->metadata;
		} else {
			$this->string = (string) $s;
			if ($m instanceof VTQL_ParseryyToken) {
				$this->metadata = $m->metadata;
			} elseif (is_array($m)) {
				$this->metadata = $m;
			}
		}
	}

	public function __toString()
	{
		return $this->_string;
	}

	public function offsetExists($offset)
	{
		return isset($this->metadata[$offset]);
	}

	public function offsetGet($offset)
	{
		return $this->metadata[$offset];
	}

	public function offsetSet($offset, $value)
	{
		if ($offset === null) {
			if (isset($value[0])) {
				$x = ($value instanceof VTQL_ParseryyToken) ?
					$value->metadata : $value;
				$this->metadata = array_merge($this->metadata, $x);
				return;
			}
			$offset = count($this->metadata);
		}
		if ($value === null) {
			return;
		}
		if ($value instanceof VTQL_ParseryyToken) {
			if ($value->metadata) {
				$this->metadata[$offset] = $value->metadata;
			}
		} elseif ($value) {
			$this->metadata[$offset] = $value;
		}
	}

	public function offsetUnset($offset)
	{
		unset($this->metadata[$offset]);
	}
}