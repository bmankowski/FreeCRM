<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * Password hashing primitives.
 *
 * Single source of truth for hashing/verifying user passwords. All call
 * sites in the application MUST go through this class - no raw
 * password_hash()/password_verify() calls anywhere else.
 *
 * Algorithm: Argon2id (memory-hard, side-channel resistant).
 * Pepper:    HMAC-SHA-256 with the per-install $user_password_pepper secret
 *            from config/config.inc.php, applied to the plaintext before
 *            password_hash. Identically applied on verify.
 *
 * Operator note: rotating $user_password_pepper invalidates every existing
 * hash. Losing it makes recovery impossible. Treat it like a database
 * encryption key.
 */

declare(strict_types=1);

namespace App\Security;

class PasswordCrypto
{
	/**
	 * Placeholder string written by config.template.php; treated as "unset".
	 */
	private const PEPPER_PLACEHOLDER = '_FREECRM_PWD_PEPPER_';

	/**
	 * Minimum acceptable pepper length in characters. 32 = 128 bits when stored
	 * as hex; the installer writes 64 hex chars (256 bits).
	 */
	private const PEPPER_MIN_LENGTH = 32;

	/**
	 * Cached resolved pepper. Loaded once per request after a successful
	 * resolve()/bootstrap pass.
	 *
	 * @var string|null
	 */
	private static $pepper;

	/**
	 * Hash a plaintext password and return the storable Argon2id digest.
	 */
	public static function hash(string $plain): string
	{
		self::assertArgon2();
		$peppered = self::pepper($plain);
		$hash = password_hash($peppered, PASSWORD_ARGON2ID, self::params());
		if (!is_string($hash) || $hash === '') {
			throw new \RuntimeException('FreeCRM: password_hash() failed (Argon2id).');
		}
		return $hash;
	}

	/**
	 * Constant-time verify of a plaintext password against a stored hash.
	 */
	public static function verify(string $plain, string $hash): bool
	{
		if ($hash === '') {
			return false;
		}
		self::assertArgon2();
		return password_verify(self::pepper($plain), $hash);
	}

	/**
	 * Whether the stored hash should be re-hashed at the current parameters.
	 * Call after a successful verify() to transparently upgrade older hashes
	 * when memory/time/threads are tuned upward.
	 */
	public static function needsRehash(string $hash): bool
	{
		if ($hash === '') {
			return true;
		}
		return password_needs_rehash($hash, PASSWORD_ARGON2ID, self::params());
	}

	/**
	 * Apply the install pepper to a plaintext password.
	 * Returns 32 raw bytes - safe input for any modern KDF.
	 */
	private static function pepper(string $plain): string
	{
		return hash_hmac('sha256', $plain, self::resolvePepper(), true);
	}

	/**
	 * Resolve the pepper from config. If missing/placeholder/too-short, try
	 * to bootstrap one into config/config.inc.php on first call. If that
	 * write fails, throw - we will not silently fall back to an insecure
	 * default.
	 */
	private static function resolvePepper(): string
	{
		if (self::$pepper !== null) {
			return self::$pepper;
		}

		$value = self::readConfiguredPepper();
		if (self::isAcceptablePepper($value)) {
			return self::$pepper = $value;
		}

		$generated = bin2hex(random_bytes(32));
		$bootstrapped = self::bootstrapIntoConfigFile($generated);
		if (!$bootstrapped) {
			throw new \RuntimeException(
				'FreeCRM password pepper missing and config/config.inc.php is not writable. '
				. 'Add `$user_password_pepper = \'<64 hex chars>\';` to config/config.inc.php manually. '
				. 'See documentation/PASSWORD_MIGRATION.md.'
			);
		}

		if (class_exists(\App\Log\Log::class)) {
			\App\Log\Log::warning('FreeCRM: $user_password_pepper was missing/placeholder; auto-generated and written to config/config.inc.php.');
		}

		$GLOBALS['user_password_pepper'] = $generated;
		return self::$pepper = $generated;
	}

	/**
	 * Read whatever the surrounding bootstrap loaded into the global scope.
	 * Mirrors how $application_unique_key is read elsewhere.
	 */
	private static function readConfiguredPepper(): string
	{
		if (isset($GLOBALS['user_password_pepper']) && is_string($GLOBALS['user_password_pepper'])) {
			return $GLOBALS['user_password_pepper'];
		}
		return '';
	}

	private static function isAcceptablePepper(string $value): bool
	{
		if ($value === '' || $value === self::PEPPER_PLACEHOLDER) {
			return false;
		}
		return strlen($value) >= self::PEPPER_MIN_LENGTH;
	}

	/**
	 * Atomically append/replace the $user_password_pepper line in
	 * config/config.inc.php. Returns true on success, false if the file is
	 * not writable.
	 */
	private static function bootstrapIntoConfigFile(string $value): bool
	{
		$path = self::configFilePath();
		if (!is_file($path) || !is_writable($path) || !is_writable(dirname($path))) {
			return false;
		}

		$contents = file_get_contents($path);
		if ($contents === false) {
			return false;
		}

		$line = "\$user_password_pepper = '" . $value . "';";
		if (preg_match('/\$user_password_pepper\s*=\s*[\'"][^\'"]*[\'"]\s*;/', $contents)) {
			$updated = preg_replace(
				'/\$user_password_pepper\s*=\s*[\'"][^\'"]*[\'"]\s*;/',
				$line,
				$contents,
				1
			);
		} else {
			$insertion = "\n// Added automatically by FreeCRM password migration. See documentation/PASSWORD_MIGRATION.md.\n" . $line . "\n";
			if (preg_match('/\?>\s*$/', $contents)) {
				$updated = preg_replace('/\?>\s*$/', $insertion . "?>\n", $contents, 1);
			} else {
				$updated = rtrim($contents, "\r\n") . "\n" . $insertion;
			}
		}

		if (!is_string($updated) || $updated === '') {
			return false;
		}

		// Preserve original ownership and mode across the atomic replace.
		// The CLI may run as root while PHP-FPM runs as www-data; tightening or
		// retitling the file would lock the web stack out of its own config.
		$origStat = @stat($path);

		$tmp = $path . '.tmp.' . bin2hex(random_bytes(4));
		if (file_put_contents($tmp, $updated, LOCK_EX) === false) {
			return false;
		}
		if (!@rename($tmp, $path)) {
			@unlink($tmp);
			return false;
		}
		if ($origStat !== false) {
			@chmod($path, $origStat['mode'] & 0777);
			if (function_exists('chown')) {
				@chown($path, $origStat['uid']);
			}
			if (function_exists('chgrp')) {
				@chgrp($path, $origStat['gid']);
			}
		}
		return true;
	}

	private static function configFilePath(): string
	{
		if (defined('ROOT_DIRECTORY')) {
			return rtrim(ROOT_DIRECTORY, '/\\') . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.inc.php';
		}
		return getcwd() . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.inc.php';
	}

	/**
	 * Resolved Argon2id parameters. Defaults match PHP 8 defaults and exceed
	 * OWASP 2024 floor (47104 KiB / 1 iter / 1 thread).
	 */
	private static function params(): array
	{
		$memory = (int) (\App\Core\AppConfig::module('Users', 'PASSWORD_ARGON2_MEMORY_COST') ?: 65536);
		$time = (int) (\App\Core\AppConfig::module('Users', 'PASSWORD_ARGON2_TIME_COST') ?: 4);
		$threads = (int) (\App\Core\AppConfig::module('Users', 'PASSWORD_ARGON2_THREADS') ?: 1);

		if ($memory < 8) {
			$memory = 65536;
		}
		if ($time < 1) {
			$time = 4;
		}
		if ($threads < 1) {
			$threads = 1;
		}

		return [
			'memory_cost' => $memory,
			'time_cost' => $time,
			'threads' => $threads,
		];
	}

	private static function assertArgon2(): void
	{
		if (!defined('PASSWORD_ARGON2ID')) {
			throw new \RuntimeException(
				'FreeCRM: PHP is not built with Argon2 support (PASSWORD_ARGON2ID undefined). '
				. 'Rebuild PHP with --with-password-argon2 (libsodium) or upgrade to a distro PHP package that ships it.'
			);
		}
	}
}
