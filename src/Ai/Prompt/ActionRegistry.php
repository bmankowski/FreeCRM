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

namespace App\Ai\Prompt;

/**
 * Stable AI action keys and their placeholder contracts.
 */
final class ActionRegistry
{
	public const MAIL_IMPROVE = 'mail.improve';

	/**
	 * @return array<string, array{label: string, placeholders: list<string>}>
	 */
	public static function all(): array
	{
		return [
			self::MAIL_IMPROVE => [
				'label' => 'LBL_ACTION_MAIL_IMPROVE',
				'placeholders' => ['subject', 'body'],
			],
		];
	}

	public static function isKnown(string $actionKey): bool
	{
		return isset(self::all()[$actionKey]);
	}

	/**
	 * @throws PromptNotFoundException
	 */
	public static function assertKnown(string $actionKey): void
	{
		if (!self::isKnown($actionKey)) {
			throw new PromptNotFoundException('Unknown AI action_key: ' . $actionKey);
		}
	}

	/**
	 * @return list<string>
	 */
	public static function placeholders(string $actionKey): array
	{
		self::assertKnown($actionKey);

		return self::all()[$actionKey]['placeholders'];
	}

	/**
	 * @return list<array{key: string, label: string, placeholders: list<string>}>
	 */
	public static function optionsForSelect(): array
	{
		$options = [];
		foreach (self::all() as $key => $meta) {
			$options[] = [
				'key' => $key,
				'label' => $meta['label'],
				'placeholders' => $meta['placeholders'],
			];
		}

		return $options;
	}
}
