<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * @project FreeCRM
 * @author bmankowski@gmail.com
 * @copyright (c) FreeCRM
 * @license FreeCRM Public License 1.1
 */

declare(strict_types=1);

namespace App\Email\Delayed;

final class Email
{
	/** @param array{to?: array, cc?: array, bcc?: array} $recipients */
	public function __construct(
		public readonly array $recipients,
		public readonly string $subject,
		public readonly string $body,
		public readonly string $senderRef = '',
	) {
	}
}
