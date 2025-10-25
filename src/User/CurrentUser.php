<?php
namespace App\User;

/**
 * Backward compatibility facade for current user access
 * DEPRECATED: Use $request->getUser() instead
 * Will be removed in version 2.0
 */
class CurrentUser
{
	private static bool $deprecationWarned = false;
	
	/**
	 * Get current user (deprecated)
	 * @deprecated Use $request->getUser() instead
	 * @return \App\Modules\Users\Models\Record|null
	 */
	public static function get(): ?\App\Modules\Users\Models\Record
	{
		self::logDeprecation();
		
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
	
	private static function logDeprecation(): void
	{
		if (!self::$deprecationWarned && \App\AppConfig::debug('LOG_TO_FILE')) {
			$trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
			$caller = $trace[1] ?? ['file' => 'unknown', 'line' => 0];
			
			\App\Log::warning(
				'DEPRECATED: CurrentUser::get() called from ' . 
				$caller['file'] . ':' . $caller['line'] . 
				'. Use $request->getUser() instead. This will be removed in v2.0'
			);
			
			self::$deprecationWarned = true;
		}
	}
}

