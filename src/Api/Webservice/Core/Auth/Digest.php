<?php
namespace App\Api\Webservice\Core\Auth;

/**
 * Digest authorization placeholder.
 *
 * Historical code referenced a digest implementation, but it relied on
 * undefined collaborators and was never functional in FreeCRM.  Keeping a
 * dedicated class allows configuration values such as AUTH_METHOD=Digest to
 * fail fast with a clear message instead of producing fatal errors.
 */
class Digest
{
	/** @var object|null */
	protected $api;

	/** @var array|null */
	protected $currentServer;

	/**
	 * Fail fast when digest auth is requested.
	 *
	 * @param string $realm
	 * @throws \RuntimeException
	 */
	public function authenticate($realm)
	{
		throw new \RuntimeException('Digest authorization is not implemented', 501);
	}

	/**
	 * Placeholder validation logic for digest auth.
	 *
	 * @param string $username
	 * @param string $password
	 * @return bool
	 */
	protected function validatePass($username, $password)
	{
		return false;
	}

	/**
	 * Provide API controller context.
	 *
	 * @param object $api
	 */
	public function setApi($api)
	{
		$this->api = $api;
	}

	/**
	 * Expose authentication payload returned to callers.
	 *
	 * @return array|null
	 */
	public function getCurrentServer()
	{
		return $this->currentServer;
	}
}
