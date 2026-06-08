<?php
/**
 * FreeCRM - LinkActionLog unit tests.
 */

declare(strict_types=1);

namespace Tests\LinkAction;

use App\Modules\LinkAction\Services\LinkActionLog;
use PHPUnit\Framework\TestCase;

final class LinkActionLogTest extends TestCase
{
	public function testParseQueueTimestampConvertsIso8601ToUtcMysql(): void
	{
		$this->assertSame(
			'2026-06-03 14:22:01',
			LinkActionLog::parseQueueTimestamp('2026-06-03T14:22:01+00:00')
		);
	}

	public function testParseQueueTimestampReturnsNullForInvalidInput(): void
	{
		$this->assertNull(LinkActionLog::parseQueueTimestamp(''));
		$this->assertNull(LinkActionLog::parseQueueTimestamp(null));
		$this->assertNull(LinkActionLog::parseQueueTimestamp('not-a-date'));
	}
}
