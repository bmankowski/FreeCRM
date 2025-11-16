<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * @project FreeCRM
 * @author bmankowski@gmail.com
 * @copyright (c) FreeCRM
 * @license FreeCRM Public License 1.0
 */

declare(strict_types=1);

namespace App\ModuleManagement;

use App\ModuleManagement\Services;
use App\ModuleManagement\Events;

/**
 * ServiceLocator class.
 * 
 * Simple service locator for accessing ModuleManagement services.
 */
class ServiceLocator
{
	/** @var array Service instances cache */
	private static $services = [];

	/**
	 * Get ModuleService instance.
	 * 
	 * @return Services\ModuleService
	 */
	public static function getModuleService(): Services\ModuleService
	{
		if (!isset(self::$services['module'])) {
			self::$services['module'] = new Services\ModuleService(
				\App\Db\Db::getInstance(),
				self::getEventDispatcher()
			);
		}
		return self::$services['module'];
	}

	/**
	 * Get FieldService instance.
	 * 
	 * @return Services\FieldService
	 */
	public static function getFieldService(): Services\FieldService
	{
		if (!isset(self::$services['field'])) {
			self::$services['field'] = new Services\FieldService(
				\App\Db\Db::getInstance()
			);
		}
		return self::$services['field'];
	}

	/**
	 * Get BlockService instance.
	 * 
	 * @return Services\BlockService
	 */
	public static function getBlockService(): Services\BlockService
	{
		if (!isset(self::$services['block'])) {
			self::$services['block'] = new Services\BlockService(
				\App\Db\Db::getInstance()
			);
		}
		return self::$services['block'];
	}

	/**
	 * Get RelationService instance.
	 * 
	 * @return Services\RelationService
	 */
	public static function getRelationService(): Services\RelationService
	{
		if (!isset(self::$services['relation'])) {
			self::$services['relation'] = new Services\RelationService(
				\App\Db\Db::getInstance()
			);
		}
		return self::$services['relation'];
	}

	/**
	 * Get EventDispatcher instance.
	 * 
	 * @return Events\Dispatcher
	 */
	public static function getEventDispatcher(): Events\Dispatcher
	{
		if (!isset(self::$services['eventDispatcher'])) {
			self::$services['eventDispatcher'] = new Events\Dispatcher();
		}
		return self::$services['eventDispatcher'];
	}

	/**
	 * Get PackageService instance.
	 * 
	 * @return Services\PackageService
	 */
	public static function getPackageService(): Services\PackageService
	{
		if (!isset(self::$services['package'])) {
			self::$services['package'] = new Services\PackageService(
				\App\Db\Db::getInstance(),
				self::getEventDispatcher()
			);
		}
		return self::$services['package'];
	}

	/**
	 * Get FileService instance.
	 * 
	 * @return Services\FileService
	 */
	public static function getFileService(): Services\FileService
	{
		if (!isset(self::$services['file'])) {
			self::$services['file'] = new Services\FileService();
		}
		return self::$services['file'];
	}

	/**
	 * Get ProfileService instance.
	 * 
	 * @return Services\ProfileService
	 */
	public static function getProfileService(): Services\ProfileService
	{
		if (!isset(self::$services['profile'])) {
			self::$services['profile'] = new Services\ProfileService(
				\App\Db\Db::getInstance()
			);
		}
		return self::$services['profile'];
	}

	/**
	 * Get AccessService instance.
	 * 
	 * @return Services\AccessService
	 */
	public static function getAccessService(): Services\AccessService
	{
		if (!isset(self::$services['access'])) {
			self::$services['access'] = new Services\AccessService(
				\App\Db\Db::getInstance(),
				self::getProfileService()
			);
		}
		return self::$services['access'];
	}

	/**
	 * Get FilterService instance.
	 * 
	 * @return Services\FilterService
	 */
	public static function getFilterService(): Services\FilterService
	{
		if (!isset(self::$services['filter'])) {
			self::$services['filter'] = new Services\FilterService(
				\App\Db\Db::getInstance()
			);
		}
		return self::$services['filter'];
	}

	/**
	 * Get LinkService instance.
	 * 
	 * @return Services\LinkService
	 */
	public static function getLinkService(): Services\LinkService
	{
		if (!isset(self::$services['link'])) {
			self::$services['link'] = new Services\LinkService(
				\App\Db\Db::getInstance()
			);
		}
		return self::$services['link'];
	}

	/**
	 * Get CronService instance.
	 * 
	 * @return Services\CronService
	 */
	public static function getCronService(): Services\CronService
	{
		if (!isset(self::$services['cron'])) {
			self::$services['cron'] = new Services\CronService(
				\App\Db\Db::getInstance()
			);
		}
		return self::$services['cron'];
	}

	/**
	 * Get WebserviceService instance.
	 * 
	 * @return Services\WebserviceService
	 */
	public static function getWebserviceService(): Services\WebserviceService
	{
		if (!isset(self::$services['webservice'])) {
			self::$services['webservice'] = new Services\WebserviceService();
		}
		return self::$services['webservice'];
	}

	/**
	 * Get LanguageService instance.
	 * 
	 * @return Services\LanguageService
	 */
	public static function getLanguageService(): Services\LanguageService
	{
		if (!isset(self::$services['language'])) {
			self::$services['language'] = new Services\LanguageService();
		}
		return self::$services['language'];
	}

	/**
	 * Get MenuService instance.
	 * 
	 * @return Services\MenuService
	 */
	public static function getMenuService(): Services\MenuService
	{
		if (!isset(self::$services['menu'])) {
			self::$services['menu'] = new Services\MenuService(
				\App\Db\Db::getInstance()
			);
		}
		return self::$services['menu'];
	}
}

