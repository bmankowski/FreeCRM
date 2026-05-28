<?php
/**
 * FreeCRM - Customer Relationship Management System
 */

declare(strict_types=1);

namespace Tests\PhpUnit\RecruitmentApplication;

use App\Modules\RecruitmentApplication\Services\CvImport\ApplicationNumberResolver;
use PHPUnit\Framework\TestCase;

class ApplicationNumberResolverTest extends TestCase
{
	public function testReferralFilenameWithoutUnderscore(): void
	{
		$this->assertSame(
			'PolecZnajomego',
			ApplicationNumberResolver::fromJsonPath('/import/cv/pending/PolecZnajomego.json')
		);
	}

	public function testJetFormFilenameWithUnderscore(): void
	{
		$this->assertSame(
			'1738743423',
			ApplicationNumberResolver::fromJsonPath('/import/cv/pending/apply_1738743423.json')
		);
	}

	public function testLeaveCvFilenameUsesFullBasename(): void
	{
		$this->assertSame(
			'leavecv_2026-05-05_09-46-10',
			ApplicationNumberResolver::fromJsonPath('/import/cv/pending/leavecv_2026-05-05_09-46-10.json')
		);
	}
}
