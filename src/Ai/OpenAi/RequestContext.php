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

/**
 * Labels an OpenAI call for ai.log (action + optional user).
 */
final class RequestContext
{
	public const ACTION_PROVIDER_LIST_MODELS = 'provider.list_models';

	public readonly string $action;

	public readonly ?int $userId;

	public function __construct(string $action, ?int $userId = null)
	{
		$action = trim($action);
		if ($action === '') {
			throw new \InvalidArgumentException('AI RequestContext.action is required');
		}
		$this->action = $action;
		$this->userId = $userId;
	}
}
