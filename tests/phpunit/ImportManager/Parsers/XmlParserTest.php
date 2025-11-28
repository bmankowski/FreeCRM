<?php
/**
 * XML parser smoke tests for ImportManager.
 */

declare(strict_types=1);

namespace Tests\PhpUnit\ImportManager\Parsers;

use App\Modules\ImportManager\Parsers\XmlParser;
use PHPUnit\Framework\TestCase;

class XmlParserTest extends TestCase
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

	public function testReadPreviewExtractsRows(): void
	{
		$xml = <<<XML
<?xml version="1.0"?>
<Records>
	<Record>
		<Name>John</Name>
		<Email>john@example.com</Email>
	</Record>
	<Record>
		<Name>Jane</Name>
		<Email>jane@example.com</Email>
	</Record>
</Records>
XML;
		$file = $this->createTempFile($xml);
		$parser = new XmlParser($file, ['xpath' => '/Records/Record']);

		$rows = $parser->readPreview(10);

		$this->assertCount(2, $rows);
		$this->assertContains('John', $rows[0]);
		$this->assertContains('john@example.com', $rows[0]);
	}

	public function testIterateReturnsAllRecords(): void
	{
		$xml = <<<XML
<?xml version="1.0"?>
<Items>
	<Row><Value>A</Value></Row>
	<Row><Value>B</Value></Row>
	<Row><Value>C</Value></Row>
</Items>
XML;
		$file = $this->createTempFile($xml);
		$parser = new XmlParser($file, ['xpath' => '/Items/Row']);

		$rows = [];
		$parser->iterate(static function (array $row) use (&$rows) {
			$rows[] = $row;
		});

		$this->assertCount(3, $rows);
		$this->assertContains('A', $rows[0]);
		$this->assertContains('C', $rows[2]);
	}

	private function createTempFile(string $contents): string
	{
		$file = tempnam(sys_get_temp_dir(), 'xmlparser');
		if ($file === false) {
			$this->fail('Unable to create temp file for XmlParser test.');
		}
		file_put_contents($file, $contents);
		$this->tempFiles[] = $file;
		return $file;
	}
}

