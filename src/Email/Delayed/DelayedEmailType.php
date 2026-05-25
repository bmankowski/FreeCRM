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

enum DelayedEmailType: string
{
	case STATUS_CHANGE = 'status_change';

	public function resolver(): ?RelevanceResolver
	{
		return match ($this) {
			self::STATUS_CHANGE => new Resolvers\StatusChangeResolver(),
		};
	}
}
