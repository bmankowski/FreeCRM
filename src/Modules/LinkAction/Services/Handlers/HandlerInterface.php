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

namespace App\Modules\LinkAction\Services\Handlers;

interface HandlerInterface
{
	public function supports(string $moduleName, string $action, string $scope): bool;

	/**
	 * @param array<string, mixed> $payload
	 */
	public function handle(array $payload): void;
}
