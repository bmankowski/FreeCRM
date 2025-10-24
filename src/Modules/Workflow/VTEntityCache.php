<?php

namespace App\Modules\Workflow;

class VTEntityCache
{

    /** @var array */
    protected $cache;
    /** @var mixed */
    protected $user;


	function __construct($user)
	{
		$this->user = $user;
		$this->cache = [];
	}

	static $_vtWorflow_entity_cache = [];

	function forId($id)
	{
		if (!isset($this->cache[$id])) {
			$entity = VTEntityCache::getCachedEntity($id);
			if (!$entity) {
				$data = new VTWorkflowEntity($this->user, $id);
				$this->cache[$id] = $data;
			} else {
				return $entity;
			}
		}
		return $this->cache[$id];
	}

	public static function getCachedEntity($id)
	{
		if (isset(self::$_vtWorflow_entity_cache[$id])) {
			return self::$_vtWorflow_entity_cache[$id];
		}
		return false;
	}

	public static function setCachedEntity($id, $entity)
	{
		self::$_vtWorflow_entity_cache[$id] = $entity;
	}
}