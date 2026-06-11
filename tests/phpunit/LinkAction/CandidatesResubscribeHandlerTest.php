<?php
/**
 * FreeCRM - CandidatesResubscribeHandler unit tests.
 */

declare(strict_types=1);

namespace Tests\PhpUnit\LinkAction;

use App\Modules\LinkAction\Services\Handlers\CandidatesResubscribeHandler;
use PHPUnit\Framework\TestCase;

final class CandidatesResubscribeHandlerTest extends TestCase
{
	public function testSupportsResubscribeAction(): void
	{
		$handler = new CandidatesResubscribeHandler();
		$this->assertTrue($handler->supports('Candidates', 'resubscribe', 'future_contact'));
		$this->assertFalse($handler->supports('Candidates', 'resubscribe', 'all'));
		$this->assertFalse($handler->supports('Candidates', 'unsubscribe', 'future_contact'));
		$this->assertFalse($handler->supports('Leads', 'resubscribe', 'future_contact'));
	}

	public function testHandleRejectsInvalidRecordId(): void
	{
		$handler = new CandidatesResubscribeHandler();
		$this->expectException(\RuntimeException::class);
		$this->expectExceptionMessage('Invalid Candidates resubscribe payload');
		$handler->handle([
			'module' => 'Candidates',
			'record_id' => 0,
			'email_field' => 'email_private',
			'eh' => str_repeat('a', 64),
		]);
	}

	public function testHandleRejectsDisallowedEmailField(): void
	{
		$handler = new CandidatesResubscribeHandler();
		$this->expectException(\RuntimeException::class);
		$this->expectExceptionMessage('Invalid Candidates resubscribe payload');
		$handler->handle([
			'module' => 'Candidates',
			'record_id' => 1,
			'email_field' => 'not_a_real_field',
			'eh' => str_repeat('a', 64),
		]);
	}
}
