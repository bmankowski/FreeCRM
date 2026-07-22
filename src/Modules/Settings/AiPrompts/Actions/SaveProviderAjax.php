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

namespace App\Modules\Settings\AiPrompts\Actions;

use App\Ai\OpenAi\Client;
use App\Ai\OpenAi\OpenAiException;
use App\Ai\OpenAi\RequestContext;
use App\Modules\Settings\AiPrompts\Models\ProviderConfig;

class SaveProviderAjax extends \App\Modules\Settings\Base\Views\IndexAjax
{
	public function __construct()
	{
		parent::__construct();
		$this->exposeMethod('save');
		$this->exposeMethod('listModels');
	}

	public function checkPermission(\App\Http\Vtiger_Request $request): void
	{
		if (!$request->getUser()->isAdminUser()) {
			throw new \App\Exceptions\NoPermittedForAdmin('LBL_PERMISSION_DENIED');
		}
	}

	public function save(\App\Http\Vtiger_Request $request): void
	{
		$data = $this->rawParam($request);
		$model = (string) ($data['model'] ?? '');
		$apiKeyRaw = (string) ($data['api_key'] ?? '');
		$clearKey = !empty($data['clear_api_key']);

		$apiKey = null;
		if (!$clearKey && $apiKeyRaw !== '' && !ProviderConfig::looksLikeMaskedOrPlaceholder($apiKeyRaw)) {
			$apiKey = $apiKeyRaw;
		}

		try {
			ProviderConfig::save($apiKey, $model, $clearKey);
		} catch (\InvalidArgumentException|OpenAiException $e) {
			$this->emitError($e);
			return;
		}

		$this->emitSuccess(\App\Runtime\Vtiger_Language_Handler::translate('LBL_SAVE_NOTIFY_OK', 'Vtiger'));
	}

	public function listModels(\App\Http\Vtiger_Request $request): void
	{
		$data = $this->rawParam($request);
		$formKey = isset($data['api_key']) ? (string) $data['api_key'] : null;

		try {
			$apiKey = ProviderConfig::resolveApiKeyForRequest($formKey);
			$userId = (int) $request->getUser()->getId();
			$models = (new Client())->listChatModels(
				$apiKey,
				new RequestContext(RequestContext::ACTION_PROVIDER_LIST_MODELS, $userId > 0 ? $userId : null)
			);
		} catch (OpenAiException $e) {
			$this->emitError($e);
			return;
		}

		$response = new \App\Http\Vtiger_Response();
		$response->setResult([
			'success' => true,
			'models' => $models,
			'count' => count($models),
			'message' => \App\Runtime\Vtiger_Language_Handler::translate(
				'LBL_AI_MODELS_FETCHED',
				'Settings:AiPrompts',
				count($models)
			),
		]);
		$response->emit();
	}

	/**
	 * @return array<string, mixed>
	 */
	private function rawParam(\App\Http\Vtiger_Request $request): array
	{
		$raw = $request->getRaw('param', []);
		if (is_string($raw) && $raw !== '') {
			$decoded = json_decode($raw, true);
			$raw = is_array($decoded) ? $decoded : [];
		}
		if (!is_array($raw)) {
			$fallback = $request->get('param');
			$raw = is_array($fallback) ? $fallback : [];
		}

		return $raw;
	}

	private function emitSuccess(string $message): void
	{
		$response = new \App\Http\Vtiger_Response();
		$response->setResult([
			'success' => true,
			'message' => $message,
		]);
		$response->emit();
	}

	private function emitError(\Throwable $e): void
	{
		$key = $e->getMessage();
		$translated = \App\Runtime\Vtiger_Language_Handler::translate($key, 'Settings:AiPrompts');
		$message = $translated !== $key ? $translated : $key;
		if ($e instanceof OpenAiException && $e->getApiMessage()) {
			$message .= ' (' . $e->getApiMessage() . ')';
		}
		$response = new \App\Http\Vtiger_Response();
		$response->setResult([
			'success' => false,
			'message' => $message,
		]);
		$response->emit();
	}
}
