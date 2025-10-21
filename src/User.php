<?php
namespace App;

/**
 * User basic class
 * @package YetiForce.App
 * @license licenses/License.html
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 * @author Radosław Skrzypczak <r.skrzypczak@yetiforce.com>
 */
class User
{
	protected static $currentUserId;
	protected static $currentUserRealId = false;
	protected static $currentUserCache = false;

	/**
	 * Get current user Id
	 * @return int
	 */
	public static function getCurrentUserId()
	{
		return static::$currentUserId;
	}

	/**
	 * Set current user Id
	 * @param int $userId
	 */
	public static function setCurrentUserId($userId)
	{
		static::$currentUserId = $userId;
		static::$currentUserCache = false; // Invalidate cache
	}

	/**
	 * Get real current user Id
	 * @return int
	 */
	public static function getCurrentUserRealId()
	{
		if (static::$currentUserRealId) {
			return static::$currentUserRealId;
		}
		if (\App\Http\Vtiger_Session::has('baseUserId') && \App\Http\Vtiger_Session::get('baseUserId')) {
			$id = \App\Http\Vtiger_Session::get('baseUserId');
		} else {
			$id = static::getCurrentUserId();
		}
		static::$currentUserRealId = $id;
		return $id;
	}

	/**
	 * Get current user model (returns full Record model)
	 * @return \App\Modules\Users\Models\Record
	 */
	public static function getCurrentUserModel()
	{
		if (static::$currentUserCache) {
			return static::$currentUserCache;
		}
		if (!static::$currentUserId) {
			static::$currentUserId = (int) \App\Http\Vtiger_Session::get('authenticated_user_id');
		}
		return static::$currentUserCache = \App\Modules\Users\Models\Record::getInstanceById(
			static::$currentUserId,
			'Users'
		);
	}

	/**
	 * Clear user cache
	 * @param int|boolean $userId
	 */
	public static function clearCache($userId = false)
	{
		if ($userId) {
			\App\Modules\Users\Models\Privileges::clearCache($userId);
			if (static::$currentUserId === $userId) {
				static::$currentUserCache = false;
			}
		} else {
			static::$currentUserCache = false;
			\App\Modules\Users\Models\Privileges::clearCache();
		}
	}
}
