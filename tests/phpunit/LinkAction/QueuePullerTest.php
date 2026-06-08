<?php
/**
 * FreeCRM - Customer Relationship Management System
 */

declare(strict_types=1);

namespace Tests\PhpUnit\LinkAction;

use App\Modules\LinkAction\Services\FilePaths;
use App\Modules\LinkAction\Services\QueueHttpClient;
use App\Modules\LinkAction\Services\QueuePuller;
use PHPUnit\Framework\TestCase;

class QueuePullerTest extends TestCase
{
	private string $incomingFile;

	/** @var list<array{method: string, url: string, apiKey: string, timeoutSeconds: int}> */
	private array $requests = [];

	/** @var list<array{status: int, body: string, error: string}> */
	private array $responses = [];

	protected function setUp(): void
	{
		FilePaths::ensureDirectories();
		$this->incomingFile = FilePaths::incomingQueueFile();
		@unlink($this->incomingFile);
		$this->requests = [];
		$this->responses = [];
	}

	protected function tearDown(): void
	{
		@unlink($this->incomingFile);
		foreach (glob(dirname($this->incomingFile) . '/queue.jsonl.tmp.*') ?: [] as $tempFile) {
			@unlink($tempFile);
		}
	}

	public function testFetchWritesJsonlOn200(): void
	{
		$this->responses[] = [
			'status' => 200,
			'body' => '{"ts":"2026-06-04T00:00:00+00:00","t":"token","fp":"abc"}',
			'error' => '',
		];

		$puller = new QueuePuller($this->createHttpClient());
		$this->assertTrue($puller->fetch());
		$this->assertFileExists($this->incomingFile);
		$contents = (string) file_get_contents($this->incomingFile);
		$this->assertStringContainsString('"t":"token"', $contents);
		$this->assertSame('GET', $this->requests[0]['method']);
	}

	public function testFetchReturnsFalseOn204(): void
	{
		$this->responses[] = [
			'status' => 204,
			'body' => '',
			'error' => '',
		];

		$puller = new QueuePuller($this->createHttpClient());
		$this->assertFalse($puller->fetch());
		$this->assertFileDoesNotExist($this->incomingFile);
	}

	public function testFetchReturnsFalseOn404(): void
	{
		$this->responses[] = [
			'status' => 404,
			'body' => '',
			'error' => '',
		];

		$puller = new QueuePuller($this->createHttpClient());
		$this->assertFalse($puller->fetch());
	}

	public function testAckReturnsTrueOn204(): void
	{
		$this->responses[] = [
			'status' => 204,
			'body' => '',
			'error' => '',
		];

		$puller = new QueuePuller($this->createHttpClient());
		$this->assertTrue($puller->ack());
		$this->assertSame('POST', $this->requests[0]['method']);
	}

	public function testFetchAppendsToExistingIncomingFile(): void
	{
		file_put_contents($this->incomingFile, "existing-line\n");
		$this->responses[] = [
			'status' => 200,
			'body' => '{"ts":"2026-06-04T00:00:00+00:00","t":"new","fp":"def"}',
			'error' => '',
		];

		$puller = new QueuePuller($this->createHttpClient());
		$this->assertTrue($puller->fetch());
		$contents = (string) file_get_contents($this->incomingFile);
		$this->assertStringContainsString('existing-line', $contents);
		$this->assertStringContainsString('"t":"new"', $contents);
	}

	private function createHttpClient(): QueueHttpClient
	{
		$testCase = $this;
		return new class($testCase) extends QueueHttpClient {
			public function __construct(private QueuePullerTest $testCase)
			{
			}

			public function request(string $method, string $url, string $apiKey, int $timeoutSeconds): array
			{
				$this->testCase->recordRequest($method, $url, $apiKey, $timeoutSeconds);
				return $this->testCase->nextResponse();
			}
		};
	}

	public function recordRequest(string $method, string $url, string $apiKey, int $timeoutSeconds): void
	{
		$this->requests[] = [
			'method' => $method,
			'url' => $url,
			'apiKey' => $apiKey,
			'timeoutSeconds' => $timeoutSeconds,
		];
	}

	/**
	 * @return array{status: int, body: string, error: string}
	 */
	public function nextResponse(): array
	{
		if ($this->responses === []) {
			$this->fail('Unexpected HTTP request in QueuePullerTest');
		}

		return array_shift($this->responses);
	}
}
