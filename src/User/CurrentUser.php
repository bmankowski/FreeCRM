<?php
namespace App\User;

class CurrentUser
{
	/**
	 * Get current user 
	 * @return \App\Modules\Users\Models\Record|null
	 */
	public static function get(): ?\App\Modules\Users\Models\Record
	{
		
		// Try request first
		$request = new \App\Http\Vtiger_Request($_REQUEST, $_REQUEST);
		if ($request->hasUser()) {
			return $request->getUser();
		}
		
		// Fallback to session hydration
		$userId = \App\Http\Vtiger_Session::getAuthenticatedUserId();
		if ($userId) {
			return \App\Modules\Users\Models\Record::getInstanceById($userId, 'Users');
		}
		
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

