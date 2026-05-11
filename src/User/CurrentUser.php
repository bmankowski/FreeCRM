<?php
namespace App\User;

class CurrentUser
{
	private static ?\App\Modules\Users\Models\Record $user = null;
	private static ?int $userId = null;

	/**
	 * Get current user 
	 * @return \App\Modules\Users\Models\Record|null
	 */
	public static function get(): ?\App\Modules\Users\Models\Record
	{
		
		// Try request first
		$request = new \App\Http\Vtiger_Request($_REQUEST, $_REQUEST);
		if ($request->hasUser()) {
			self::$user = $request->getUser();
			self::$userId = self::$user ? self::$user->getId() : null;
			return self::$user;
		}
		
		// Fallback to session hydration
		$userId = \App\Http\Vtiger_Session::getAuthenticatedUserId();
		if ($userId) {
			if (self::$user && self::$userId === $userId) {
				return self::$user;
			}
			self::$userId = $userId;
			return self::$user = \App\Modules\Users\Models\Record::getInstanceById($userId, 'Users');
		}
		
		self::$user = null;
		self::$userId = null;
		return null;
	}
	
	/**
	 * Get current user ID
	 * @deprecated Use $request->getUserId() instead
	 */
	public static function getId(): ?int
	{
		$user = self::get();
		return $user ? $user->getId() : null;
	}
	
	
}

