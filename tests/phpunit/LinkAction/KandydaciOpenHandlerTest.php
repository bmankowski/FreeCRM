<?php
/**
 * FreeCRM - KandydaciOpenHandler unit tests.
 */

declare(strict_types=1);

namespace Tests\PhpUnit\LinkAction;

use App\Modules\LinkAction\Services\Handlers\KandydaciOpenHandler;
use PHPUnit\Framework\TestCase;

final class KandydaciOpenHandlerTest extends TestCase
{
	public function testSupportsOpenAction(): void
	{
		$handler = new KandydaciOpenHandler();
		$this->assertTrue($handler->supports('Kandydaci', 'open', 'email'));
		$this->assertFalse($handler->supports('Kandydaci', 'open', 'future_contact'));
		$this->assertFalse($handler->supports('Kandydaci', 'unsubscribe', 'email'));
		$this->assertFalse($handler->supports('Leads', 'open', 'email'));
	}

	public function testHandleRejectsInvalidRecordId(): void
	{
		$handler = new KandydaciOpenHandler();
		$this->expectException(\RuntimeException::class);
		$this->expectExceptionMessage('Invalid Kandydaci open payload');
		$handler->handle([
			'module' => 'Kandydaci',
			'record_id' => 0,
			'email_field' => 'newsletter_email',
			'eh' => str_repeat('a', 64),
		]);
	}

	public function testHandleRejectsDisallowedEmailField(): void
	{
		$handler = new KandydaciOpenHandler();
		$this->expectException(\RuntimeException::class);
		$this->expectExceptionMessage('Invalid Kandydaci open payload');
		$handler->handle([
			'module' => 'Kandydaci',
			'record_id' => 1,
			'email_field' => 'not_a_real_field',
			'eh' => str_repeat('a', 64),
		]);
	}
}
