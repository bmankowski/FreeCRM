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

namespace App\Ai\OpenAi;

final class OpenAiException extends \RuntimeException
{
	private ?string $apiMessage;

	public function __construct(string $message, ?string $apiMessage = null, int $code = 0, ?\Throwable $previous = null)
	{
		parent::__construct($message, $code, $previous);
		$this->apiMessage = $apiMessage;
	}

	public function getApiMessage(): ?string
	{
		return $this->apiMessage;
	}
}
