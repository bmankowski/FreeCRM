<?php
/**
 * FreeCRM - Customer Relationship Management System
 */

declare(strict_types=1);

namespace Tests\PhpUnit\RecruitmentApplication;

use App\Modules\RecruitmentApplication\Services\CvImport\CvJsonParser;
use PHPUnit\Framework\TestCase;

class CvJsonParserTest extends TestCase
{
	private string $fixturesDir;

	protected function setUp(): void
	{
		$this->fixturesDir = dirname(__DIR__, 2) . '/fixtures/cv-import/';
	}

	public function testParseJetFormFixture(): void
	{
		$path = $this->fixturesDir . 'jetform_apply.json';
		$dto = CvJsonParser::parseFile(dirname($path) . '/', $path, '1738743423');
		$this->assertFalse($dto->isMetForm);
		$this->assertSame('Testowy Kandydat', $dto->candidateName);
		$this->assertSame('test@itconnect.pl', $dto->candidateEmail);
		$this->assertSame('42', $dto->sourceId);
		$this->assertSame('1420532', $dto->projectId);
		$this->assertSame('apply', $dto->formType);
	}

	public function testParseMetFormFixture(): void
	{
		$path = $this->fixturesDir . 'metform_cv.json';
		$dto = CvJsonParser::parseFile(dirname($path) . '/', $path, '1738743423');
		$this->assertTrue($dto->isMetForm);
		$this->assertStringContainsString('Jan', $dto->candidateName);
		$this->assertSame('jan.kowalski@example.com', $dto->candidateEmail);
		$this->assertSame('5', $dto->sourceId);
		$this->assertSame('100', $dto->projectId);
	}
}
