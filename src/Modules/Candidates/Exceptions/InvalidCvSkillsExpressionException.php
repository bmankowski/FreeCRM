<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * @project FreeCRM
 * @author bmankowski@gmail.com
 * @copyright (c) FreeCRM
 * @license FreeCRM Public License 1.0
 */

declare(strict_types=1);

namespace App\Modules\Candidates\Exceptions;

class InvalidCvSkillsExpressionException extends \InvalidArgumentException
{
	public function __construct(
		private readonly string $messageKey = 'LBL_KANBAN_CV_SKILLS_INVALID',
		private readonly string $detail = '',
	) {
		parent::__construct($messageKey . ($detail !== '' ? ': ' . $detail : ''));
	}

	public function getMessageKey(): string
	{
		return $this->messageKey;
	}

	public function getDetail(): string
	{
		return $this->detail;
	}
}
