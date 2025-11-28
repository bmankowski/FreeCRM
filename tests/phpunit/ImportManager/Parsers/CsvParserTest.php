<?php
/**
 * CSV parser smoke tests for ImportManager.
 */

declare(strict_types=1);

namespace Tests\PhpUnit\ImportManager\Parsers;

use App\Modules\ImportManager\Parsers\CsvParser;
use PHPUnit\Framework\TestCase;

class CsvParserTest extends TestCase
{
	private array $tempFiles = [];

	protected function tearDown(): void
	{
		foreach ($this->tempFiles as $file) {
			if (is_file($file)) {
				@unlink($file);
			}
		}
		$this->tempFiles = [];
		parent::tearDown();
	}

	public function testReadPreviewParsesHeadersAndRows(): void
	{
		$file = $this->createTempFile("Name,Email\nJohn,john@example.com\nJane,jane@example.com\n");
		$parser = new CsvParser($file, []);

		$rows = $parser->readPreview(10);

		$this->assertSame([['John', 'john@example.com'], ['Jane', 'jane@example.com']], $rows);
		$this->assertSame(['Name', 'Email'], $parser->getHeaders());
	}

	public function testIterateReadsAllRows(): void
	{
		$file = $this->createTempFile("A;B;C\n1;2;3\n4;5;6\n");
		$parser = new CsvParser($file, ['delimiter' => ';']);

		$rows = [];
		$parser->iterate(static function (array $row) use (&$rows) {
			$rows[] = $row;
		});

		$this->assertCount(2, $rows);
		$this->assertSame(['1', '2', '3'], $rows[0]);
		$this->assertSame(['4', '5', '6'], $rows[1]);
	}

	private function createTempFile(string $contents): string
	{
		$file = tempnam(sys_get_temp_dir(), 'csvparser');
		if ($file === false) {
			$this->fail('Unable to create temp file for CsvParser test.');
		}
		file_put_contents($file, $contents);
		$this->tempFiles[] = $file;
		return $file;
	}
}

