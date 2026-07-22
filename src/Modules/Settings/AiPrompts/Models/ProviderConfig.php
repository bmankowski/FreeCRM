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

namespace App\Modules\Settings\AiPrompts\Models;

use App\Ai\OpenAi\OpenAiException;

/**
 * Single-row OpenAI provider configuration.
 */
final class ProviderConfig
{
	public const PROVIDER = 'openai';
	public const DEFAULT_MODEL = 'gpt-5-nano';

	/** @return list<string> */
	public static function suggestedModels(): array
	{
		return ['gpt-5-nano', 'gpt-5.4-nano', 'gpt-4o-mini'];
	}

	/**
	 * @return array{id: int, provider: string, api_key: ?string, model: string, has_api_key: bool, modifiedtime: string}
	 */
	public static function get(): array
	{
		$row = (new \App\Db\Query())
			->from('s_#__ai_provider')
			->where(['provider' => self::PROVIDER])
			->one();
		if (!$row) {
			return [
				'id' => 0,
				'provider' => self::PROVIDER,
				'api_key' => null,
				'model' => self::DEFAULT_MODEL,
				'has_api_key' => false,
				'modifiedtime' => '',
			];
		}
		$key = $row['api_key'] ?? null;
		$key = is_string($key) && $key !== '' ? $key : null;

		return [
			'id' => (int) $row['id'],
			'provider' => (string) $row['provider'],
			'api_key' => $key,
			'model' => (string) ($row['model'] ?: self::DEFAULT_MODEL),
			'has_api_key' => $key !== null,
			'modifiedtime' => (string) ($row['modifiedtime'] ?? ''),
		];
	}

	/**
	 * @throws OpenAiException
	 * @throws \InvalidArgumentException
	 */
	public static function save(?string $apiKey, string $model, bool $clearApiKey = false): void
	{
		$model = trim($model);
		if ($model === '') {
			throw new \InvalidArgumentException('LBL_AI_MODEL_MISSING');
		}

		$current = self::get();
		$now = date('Y-m-d H:i:s');
		$params = [
			'model' => $model,
			'modifiedtime' => $now,
		];

		if ($clearApiKey) {
			$params['api_key'] = null;
		} elseif ($apiKey !== null) {
			$trimmed = trim($apiKey);
			if ($trimmed !== '') {
				self::assertLooksLikeApiKey($trimmed);
				$params['api_key'] = $trimmed;
			}
		}

		$db = \App\Db\Db::getInstance();
		if ($current['id'] > 0) {
			$db->createCommand()->update('s_#__ai_provider', $params, [
				'id' => $current['id'],
			])->execute();
			return;
		}

		$params['provider'] = self::PROVIDER;
		if (!array_key_exists('api_key', $params)) {
			$params['api_key'] = null;
		}
		$db->createCommand()->insert('s_#__ai_provider', $params)->execute();
	}

	/**
	 * @throws OpenAiException
	 * @return array{api_key: string, model: string}
	 */
	public static function requireConfigured(): array
	{
		$config = self::get();
		if (!$config['has_api_key'] || $config['api_key'] === null) {
			throw new OpenAiException('LBL_AI_API_KEY_MISSING');
		}

		return [
			'api_key' => $config['api_key'],
			'model' => $config['model'],
		];
	}

	public static function maskedKeyHint(bool $hasKey): string
	{
		return $hasKey ? '••••••••••••••••' : '';
	}

	public static function looksLikeMaskedOrPlaceholder(string $value): bool
	{
		$value = trim($value);
		if ($value === '' || $value === self::maskedKeyHint(true)) {
			return true;
		}

		return (bool) preg_match('/^[•·∙●\*]+$/u', $value);
	}

	/**
	 * Resolve usable API key from form value or stored config.
	 * Empty / masked form value → stored key.
	 *
	 * @throws OpenAiException
	 */
	public static function resolveApiKeyForRequest(?string $formApiKey): string
	{
		$formApiKey = $formApiKey !== null ? trim($formApiKey) : '';
		if ($formApiKey !== '' && !self::looksLikeMaskedOrPlaceholder($formApiKey)) {
			self::assertLooksLikeApiKey($formApiKey);

			return $formApiKey;
		}
		$config = self::get();
		if ($config['has_api_key'] && is_string($config['api_key']) && $config['api_key'] !== '') {
			return $config['api_key'];
		}
		throw new OpenAiException('LBL_AI_API_KEY_MISSING');
	}

	/**
	 * @throws OpenAiException
	 */
	public static function assertLooksLikeApiKey(string $apiKey): void
	{
		$apiKey = trim($apiKey);
		if ($apiKey === '' || !str_starts_with($apiKey, 'sk-')) {
			throw new OpenAiException('LBL_AI_API_KEY_INVALID');
		}
	}
}
