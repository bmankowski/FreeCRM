<?php
declare(strict_types=1);

namespace FreeCRM\LinkAction\Www;

final class Token
{
	/** @return array<string, mixed>|null */
	public static function verify(string $token, array $config): ?array
	{
		$parts = explode('.', $token, 3);
		if (count($parts) !== 3) {
			return null;
		}
		[$kid, $payloadB64, $sigB64] = $parts;
		$keyPath = $config['public_keys'][$kid] ?? null;
		if (!is_string($keyPath) || !is_readable($keyPath)) {
			return null;
		}
		$publicKey = openssl_pkey_get_public((string) file_get_contents($keyPath));
		if ($publicKey === false) {
			return null;
		}
		$signature = self::base64UrlDecode($sigB64);
		if ($signature === null) {
			return null;
		}
		$signingInput = $kid . '.' . $payloadB64;
		if (openssl_verify($signingInput, $signature, $publicKey, OPENSSL_ALGO_SHA256) !== 1) {
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
		$now = time();
		$exp = (int) ($payload['exp'] ?? 0);
		$iat = (int) ($payload['iat'] ?? 0);
		if ($exp <= $now || $iat > ($now + 60)) {
			return null;
		}

		return $payload;
	}

	private static function base64UrlDecode(string $data): ?string
	{
		$remainder = strlen($data) % 4;
		if ($remainder > 0) {
			$data .= str_repeat('=', 4 - $remainder);
		}
		$decoded = base64_decode(strtr($data, '-_', '+/'), true);

		return $decoded === false ? null : $decoded;
	}
}
