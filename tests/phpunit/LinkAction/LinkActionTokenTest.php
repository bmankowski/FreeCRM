<?php
/**
 * FreeCRM - Customer Relationship Management System
 */

declare(strict_types=1);

namespace Tests\PhpUnit\LinkAction;

use App\Modules\LinkAction\Services\LinkActionToken;
use PHPUnit\Framework\TestCase;

class LinkActionTokenTest extends TestCase
{
	private string $fixturesDir;

	private array $config;

	protected function setUp(): void
	{
		$this->fixturesDir = dirname(__DIR__, 2) . '/fixtures/link-action/';
		$this->config = [
			'active_kid' => 'v1',
			'private_key_path' => $this->fixturesDir . 'private_v1.pem',
			'public_keys' => [
				'v1' => $this->fixturesDir . 'public_v1.pem',
			],
			'token_ttl_seconds' => 3600,
			'email_pepper' => 'test-pepper',
			'www_base_url' => 'https://itconnect.pl/la',
			'iat_skew_seconds' => 60,
		];
	}

	public function testSignVerifyRoundTrip(): void
	{
		$tokenService = new LinkActionToken($this->config);
		$payload = $tokenService->buildPayload(
			'Candidates',
			1404311,
			'newsletter_email',
			'Test@Example.com',
			'unsubscribe',
			'future_contact'
		);
		$token = $tokenService->sign($payload);
		$verified = $tokenService->verify($token);
		$this->assertIsArray($verified);
		$this->assertSame('Candidates', $verified['module']);
		$this->assertSame(1404311, $verified['record_id']);
		$this->assertSame($payload['jti'], $verified['jti']);
	}

	public function testTamperedPayloadFailsVerify(): void
	{
		$tokenService = new LinkActionToken($this->config);
		$payload = $tokenService->buildPayload(
			'Candidates',
			1404311,
			'newsletter_email',
			'test@example.com',
			'unsubscribe',
			'future_contact'
		);
		$token = $tokenService->sign($payload);
		$parts = explode('.', $token, 3);
		$parts[1] = rtrim($parts[1], 'A') . 'B';
		$this->assertNull($tokenService->verify(implode('.', $parts)));
	}

	public function testTamperedSignatureFailsVerify(): void
	{
		$tokenService = new LinkActionToken($this->config);
		$payload = $tokenService->buildPayload(
			'Candidates',
			1404311,
			'newsletter_email',
			'test@example.com',
			'unsubscribe',
			'future_contact'
		);
		$token = $tokenService->sign($payload);
		$parts = explode('.', $token, 3);
		$parts[2] = strrev($parts[2]);
		$this->assertNull($tokenService->verify(implode('.', $parts)));
	}

	public function testExpiredTokenFailsVerify(): void
	{
		$tokenService = new LinkActionToken($this->config);
		$payload = $tokenService->buildPayload(
			'Candidates',
			1404311,
			'newsletter_email',
			'test@example.com',
			'unsubscribe',
			'future_contact'
		);
		$payload['exp'] = time() - 10;
		$payload['iat'] = time() - 20;
		$this->assertNull($tokenService->verify($tokenService->sign($payload)));
	}

	public function testEmailHashNormalizesCase(): void
	{
		$tokenService = new LinkActionToken($this->config);
		$lower = $tokenService->emailHash('Candidates', 1, 'newsletter_email', 'test@example.com');
		$upper = $tokenService->emailHash('Candidates', 1, 'newsletter_email', 'TEST@EXAMPLE.COM');
		$this->assertSame($lower, $upper);
	}

	public function testTokenFingerprintIsStable(): void
	{
		$tokenService = new LinkActionToken($this->config);
		$payload = $tokenService->buildPayload(
			'Candidates',
			1404311,
			'newsletter_email',
			'test@example.com',
			'unsubscribe',
			'future_contact'
		);
		$token = $tokenService->sign($payload);
		$this->assertSame(hash('sha256', $token), $tokenService->tokenFingerprint($token));
	}

	public function testBuildImageUrlUsesOpenPath(): void
	{
		$tokenService = new LinkActionToken($this->config);
		$payload = $tokenService->buildPayload(
			'Candidates',
			1404311,
			'newsletter_email',
			'test@example.com',
			'open',
			'email',
			42
		);
		$url = $tokenService->buildImageUrl($payload);
		$this->assertStringStartsWith('https://itconnect.pl/la/o/', $url);
		$this->assertStringEndsWith('/logo.png', $url);
		$token = rawurldecode(substr($url, strlen('https://itconnect.pl/la/o/'), -strlen('/logo.png')));
		$verified = $tokenService->verify($token);
		$this->assertIsArray($verified);
		$this->assertSame(42, $verified['mid']);
	}

	public function testBuildPayloadIncludesMailMessageId(): void
	{
		$tokenService = new LinkActionToken($this->config);
		$payload = $tokenService->buildPayload(
			'Candidates',
			1,
			'newsletter_email',
			'test@example.com',
			'open',
			'email',
			99
		);
		$this->assertSame(99, $payload['mid']);
	}

	public function testSignUnsubscribeWithResubscribePairsTokens(): void
	{
		$tokenService = new LinkActionToken($this->config);
		$token = $tokenService->signUnsubscribeWithResubscribe(
			'Candidates',
			9414,
			'email_private',
			'test@example.com',
			'future_contact',
			837
		);
		$unsubPayload = $tokenService->verify($token);
		$this->assertIsArray($unsubPayload);
		$this->assertSame('unsubscribe', $unsubPayload['action']);
		$this->assertArrayHasKey('rs_t', $unsubPayload);
		$rsPayload = $tokenService->verify((string) $unsubPayload['rs_t']);
		$this->assertIsArray($rsPayload);
		$this->assertSame('resubscribe', $rsPayload['action']);
		$this->assertSame(9414, $rsPayload['record_id']);
		$this->assertSame(837, $rsPayload['mid']);
		$this->assertNotSame($unsubPayload['jti'], $rsPayload['jti']);
	}
}
