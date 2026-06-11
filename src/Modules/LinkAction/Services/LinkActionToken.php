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

namespace App\Modules\LinkAction\Services;

final class LinkActionToken
{
	private const PAYLOAD_VERSION = 1;

	private ?array $configOverride = null;

	public function __construct(?array $configOverride = null)
	{
		$this->configOverride = $configOverride;
	}

	public function sign(array $payload): string
	{
		$kid = (string) ($payload['kid'] ?? $this->config('active_kid'));
		$payloadB64 = self::base64UrlEncode(json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
		$signingInput = $kid . '.' . $payloadB64;
		$privateKey = openssl_pkey_get_private((string) file_get_contents($this->privateKeyPath($kid)));
		if ($privateKey === false) {
			throw new \RuntimeException('LinkAction private key could not be loaded');
		}
		$signature = '';
		if (!openssl_sign($signingInput, $signature, $privateKey, OPENSSL_ALGO_SHA256)) {
			throw new \RuntimeException('LinkAction token signing failed');
		}
		return $signingInput . '.' . self::base64UrlEncode($signature);
	}

	public function verify(string $token): ?array
	{
		$parts = explode('.', $token, 3);
		if (count($parts) !== 3) {
			return null;
		}
		[$kid, $payloadB64, $sigB64] = $parts;
		$publicKeyPath = $this->publicKeyPath($kid);
		if ($publicKeyPath === null || !is_readable($publicKeyPath)) {
			return null;
		}
		$publicKey = openssl_pkey_get_public((string) file_get_contents($publicKeyPath));
		if ($publicKey === false) {
			return null;
		}
		$signature = self::base64UrlDecode($sigB64);
		if ($signature === null) {
			return null;
		}
		$signingInput = $kid . '.' . $payloadB64;
		$verified = openssl_verify($signingInput, $signature, $publicKey, OPENSSL_ALGO_SHA256);
		if ($verified !== 1) {
			return null;
		}
		$payloadJson = self::base64UrlDecode($payloadB64);
		if ($payloadJson === null) {
			return null;
		}
		$payload = json_decode($payloadJson, true);
		if (!is_array($payload)) {
			return null;
		}
		if (!$this->isPayloadTimeValid($payload)) {
			return null;
		}
		return $payload;
	}

	public function buildPayload(
		string $moduleName,
		int $recordId,
		string $emailField,
		string $email,
		string $action,
		string $scope,
		?int $mailMessageId = null
	): array {
		$now = time();
		$ttl = (int) $this->config('token_ttl_seconds');
		$payload = [
			'v' => self::PAYLOAD_VERSION,
			'kid' => (string) $this->config('active_kid'),
			'module' => $moduleName,
			'record_id' => $recordId,
			'action' => $action,
			'scope' => $scope,
			'email_field' => $emailField,
			'eh' => $this->emailHash($moduleName, $recordId, $emailField, $email),
			'iat' => $now,
			'exp' => $now + $ttl,
			'jti' => bin2hex(random_bytes(16)),
		];
		if ($mailMessageId !== null && $mailMessageId > 0) {
			$payload['mid'] = $mailMessageId;
		}

		return $payload;
	}

	public function emailHash(string $moduleName, int $recordId, string $emailField, string $email): string
	{
		$material = implode('|', [
			$moduleName,
			(string) $recordId,
			$emailField,
			self::normalizeEmail($email),
			(string) $this->config('email_pepper'),
		]);
		return hash('sha256', $material);
	}

	public static function normalizeEmail(string $email): string
	{
		return strtolower(trim($email));
	}

	public function buildUrl(array $payload): string
	{
		return $this->buildUrlFromSignedToken($this->sign($payload));
	}

	public function buildUrlFromSignedToken(string $token): string
	{
		$baseUrl = rtrim((string) $this->config('www_base_url'), '/');
		return $baseUrl . '?t=' . rawurlencode($token);
	}

	public function signUnsubscribeWithResubscribe(
		string $moduleName,
		int $recordId,
		string $emailField,
		string $email,
		string $scope,
		?int $mailMessageId = null
	): string {
		$resubPayload = $this->buildPayload(
			$moduleName,
			$recordId,
			$emailField,
			$email,
			'resubscribe',
			$scope,
			$mailMessageId
		);
		$unsubPayload = $this->buildPayload(
			$moduleName,
			$recordId,
			$emailField,
			$email,
			'unsubscribe',
			$scope,
			$mailMessageId
		);
		$unsubPayload['rs_t'] = $this->sign($resubPayload);

		return $this->sign($unsubPayload);
	}

	public function buildImageUrl(array $payload): string
	{
		$baseUrl = rtrim((string) $this->config('www_base_url'), '/');
		return $baseUrl . '/o/' . rawurlencode($this->sign($payload)) . '/logo.png';
	}

	public function tokenFingerprint(string $token): string
	{
		return hash('sha256', $token);
	}

	private function isPayloadTimeValid(array $payload): bool
	{
		$now = time();
		$skew = (int) $this->config('iat_skew_seconds');
		$iat = (int) ($payload['iat'] ?? 0);
		$exp = (int) ($payload['exp'] ?? 0);
		if ($exp <= $now) {
			return false;
		}
		if ($iat > ($now + $skew)) {
			return false;
		}
		return true;
	}

	private function config(string $key): mixed
	{
		if ($this->configOverride !== null && array_key_exists($key, $this->configOverride)) {
			return $this->configOverride[$key];
		}
		return LinkActionConfig::get($key);
	}

	private function privateKeyPath(string $kid): string
	{
		if ($kid === (string) $this->config('active_kid')) {
			return (string) $this->config('private_key_path');
		}
		$path = ROOT_DIRECTORY . '/config/keys/link_action_private_' . $kid . '.pem';
		return is_readable($path) ? $path : (string) $this->config('private_key_path');
	}

	private function publicKeyPath(string $kid): ?string
	{
		$keys = $this->config('public_keys');
		if (!is_array($keys)) {
			return null;
		}
		return isset($keys[$kid]) ? (string) $keys[$kid] : null;
	}

	public static function base64UrlEncode(string $data): string
	{
		return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
	}

	public static function base64UrlDecode(string $data): ?string
	{
		$remainder = strlen($data) % 4;
		if ($remainder > 0) {
			$data .= str_repeat('=', 4 - $remainder);
		}
		$decoded = base64_decode(strtr($data, '-_', '+/'), true);
		return $decoded === false ? null : $decoded;
	}
}
