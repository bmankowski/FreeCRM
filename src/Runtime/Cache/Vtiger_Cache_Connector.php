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


namespace App\Runtime\Cache;

class Vtiger_Cache_Connector
{
	private static $selfInstance = null;
	
	private function __construct()
	{
	}
	
	public static function getInstance()
	{
		if (self::$selfInstance) {
			return self::$selfInstance;
		}

        self::$selfInstance = new self();
        return self::$selfInstance;
	}
	
	public function get(string $ns, string $key)
	{
		// Use the new \App\Cache system for compatibility
		return \App\Cache::get($ns, $ns . '_' . $key);
	}
	
	public function set(string $ns, string $key, $value)
	{
		// Use the new \App\Cache system for compatibility
		return \App\Cache::set($ns . '_' . $key, $value);
	}
	
	public function has(string $ns, string $key)
	{
		// Use the new \App\Cache system for compatibility
		return \App\Cache::has($ns . '_' . $key);
	}
	
	public function delete(string $ns, string $key)
	{
		// Use the new \App\Cache system for compatibility
		return \App\Cache::delete($ns . '_' . $key);
	}
	
	public function flush($ns = null)
	{
		// For namespace-specific flushing, we'd need to implement this differently
        // For now, just clear all cache
        return \App\Cache::clear();
	}
}
