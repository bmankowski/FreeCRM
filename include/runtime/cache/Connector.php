<?php
/* +**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Contributor(s): YetiForce.com
 * ********************************************************************************** */

class Vtiger_Cache_Connector
{
	private static $selfInstance = false;
	
	private function __construct()
	{
	}
	
	public static function getInstance()
	{
		if (self::$selfInstance) {
			return self::$selfInstance;
		} else {
			self::$selfInstance = new self();
			return self::$selfInstance;
		}
	}
	
	public function get($ns, $key)
	{
		// Use the new App\Cache system for compatibility
		return \App\Cache::get($ns . '_' . $key);
	}
	
	public function set($ns, $key, $value)
	{
		// Use the new App\Cache system for compatibility
		return \App\Cache::set($ns . '_' . $key, $value);
	}
	
	public function has($ns, $key)
	{
		// Use the new App\Cache system for compatibility
		return \App\Cache::has($ns . '_' . $key);
	}
	
	public function delete($ns, $key)
	{
		// Use the new App\Cache system for compatibility
		return \App\Cache::delete($ns . '_' . $key);
	}
	
	public function flush($ns = null)
	{
		// Use the new App\Cache system for compatibility
		if ($ns === null) {
			return \App\Cache::clear();
		} else {
			// For namespace-specific flushing, we'd need to implement this differently
			// For now, just clear all cache
			return \App\Cache::clear();
		}
	}
}
