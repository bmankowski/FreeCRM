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
 * Resolves prompt body: user override first, then system default (userid IS NULL).
 */
final class PromptResolver
{
	/**
	 * @throws PromptNotFoundException
	 */
	public static function resolve(string $actionKey, ?int $userId = null): string
	{
		ActionRegistry::assertKnown($actionKey);

		if ($userId !== null && $userId > 0) {
			$body = self::fetchActiveBody($actionKey, $userId);
			if ($body !== null) {
				return $body;
			}
		}

		$body = self::fetchActiveBody($actionKey, null);
		if ($body === null) {
			throw new PromptNotFoundException(
				'No active system prompt for action_key: ' . $actionKey
			);
		}

		return $body;
	}

	/**
	 * @param array<string, string> $vars
	 * @throws PromptNotFoundException
	 */
	public static function applyPlaceholders(string $template, array $vars): string
	{
		if (!preg_match_all('/\{\{(\w+)\}\}/', $template, $matches)) {
			return $template;
		}
		$needed = array_unique($matches[1]);
		foreach ($needed as $key) {
			if (!array_key_exists($key, $vars)) {
				throw new PromptNotFoundException('Missing prompt placeholder: {{' . $key . '}}');
			}
		}
		$replacements = [];
		foreach ($needed as $key) {
			$replacements['{{' . $key . '}}'] = (string) $vars[$key];
		}

		return strtr($template, $replacements);
	}

	private static function fetchActiveBody(string $actionKey, ?int $userId): ?string
	{
		$query = (new \App\Db\Query())
			->select(['prompt_body'])
			->from('s_#__ai_prompts')
			->where(['action_key' => $actionKey, 'active' => 1]);
		if ($userId === null) {
			$query->andWhere(['userid' => null]);
		} else {
			$query->andWhere(['userid' => $userId]);
		}
		$value = $query->scalar();
		if ($value === false || $value === null) {
			return null;
		}

		return (string) $value;
	}
}
