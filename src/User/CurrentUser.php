<?php
namespace App\User;

class CurrentUser
{
	private static ?\App\Modules\Users\Models\Record $user = null;
	private static ?int $userId = null;

	/** Imperative context (CalDAV, cron, webservice) — takes precedence over session. */
	private static ?int $contextUserId = null;

	/**
	 * Get current user
	 * @return \App\Modules\Users\Models\Record|null
	 */
	public static function get(): ?\App\Modules\Users\Models\Record
	{
		if (self::$contextUserId !== null) {
			if (self::$user !== null && self::$userId === self::$contextUserId) {
				return self::$user;
			}
			self::$userId = self::$contextUserId;
			return self::$user = \App\Modules\Users\Models\Record::getInstanceById(self::$contextUserId, 'Users');
		}

		$request = new \App\Http\Vtiger_Request($_REQUEST, $_REQUEST);
		if ($request->hasUser()) {
			self::$user = $request->getUser();
			self::$userId = self::$user ? self::$user->getId() : null;
			return self::$user;
		}

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
	 * @deprecated Use $request->getUserId() in HTTP layer
	 */
	public static function getId(): ?int
	{
		$user = self::get();
		return $user ? $user->getId() : null;
	}

	/**
	 * Set user for non-request contexts (API sync, cron). Pass null to clear override.
	 */
	public static function setContextUserId(?int $userId): void
	{
		self::$contextUserId = $userId;
		self::$user = null;
		self::$userId = $userId;
	}

	/**
	 * Drop cached user instance (e.g. after privilege file rebuild).
	 */
	public static function clearCache(): void
	{
		self::$user = null;
		if (self::$contextUserId === null) {
			self::$userId = null;
		}
	}
}
