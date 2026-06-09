<?php
/**
 * FreeCRM - CandidatesOpenHandler unit tests.
 */

declare(strict_types=1);

namespace Tests\PhpUnit\LinkAction;

use App\Modules\LinkAction\Services\Handlers\CandidatesOpenHandler;
use PHPUnit\Framework\TestCase;

final class CandidatesOpenHandlerTest extends TestCase
{
	public function testSupportsOpenAction(): void
	{
		$handler = new CandidatesOpenHandler();
		$this->assertTrue($handler->supports('Candidates', 'open', 'email'));
		$this->assertFalse($handler->supports('Candidates', 'open', 'future_contact'));
		$this->assertFalse($handler->supports('Candidates', 'unsubscribe', 'email'));
		$this->assertFalse($handler->supports('Leads', 'open', 'email'));
	}

	public function testHandleRejectsInvalidRecordId(): void
	{
		$handler = new CandidatesOpenHandler();
		$this->expectException(\RuntimeException::class);
		$this->expectExceptionMessage('Invalid Candidates open payload');
		$handler->handle([
			'module' => 'Candidates',
			'record_id' => 0,
			'email_field' => 'newsletter_email',
			'eh' => str_repeat('a', 64),
		]);
	}

	public function testHandleRejectsDisallowedEmailField(): void
	{
		$handler = new CandidatesOpenHandler();
		$this->expectException(\RuntimeException::class);
		$this->expectExceptionMessage('Invalid Candidates open payload');
		$handler->handle([
			'module' => 'Candidates',
			'record_id' => 1,
			'email_field' => 'not_a_real_field',
			'eh' => str_repeat('a', 64),
		]);
	}
}
