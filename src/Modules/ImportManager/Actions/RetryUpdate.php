<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * Saves inline edits of failed staging rows.
 */

declare(strict_types=1);

namespace App\Modules\ImportManager\Actions;

use App\Base\Controllers\BaseActionController;
use App\Modules\ImportManager\Services\BatchRepository;
use App\Modules\ImportManager\Services\RetryManager;

class RetryUpdate extends BaseActionController
{
	private RetryManager $retryManager;
	private BatchRepository $batches;

	public function __construct()
	{
		parent::__construct();
		$this->retryManager = new RetryManager();
		$this->batches = new BatchRepository();
	}

	public function checkPermission(\App\Http\Vtiger_Request $request)
	{
		if ((int) $request->get('batch_id') <= 0) {
			throw new \App\Exceptions\NoPermitted('LBL_PERMISSION_DENIED');
		}
	}

	public function process(\App\Http\Vtiger_Request $request)
	{
		$isAjax = $request->isAjax();
		$response = $isAjax ? new \App\Http\Vtiger_Response() : null;
		try {
			$batchId = (int) $request->get('batch_id');
			$batch = $this->batches->find($batchId);
			$currentUserId = \App\Modules\Users\Models\Record::getCurrentUserId();
			if (!$batch || (int) $batch['created_by'] !== (int) $currentUserId) {
				throw new \RuntimeException('Brak dostępu do wskazanego wsadu.');
			}

			if ($batch['status'] === 'running') {
				throw new \RuntimeException('Nie można edytować wsadu w trakcie przetwarzania.');
			}

			$rowsPayload = $this->decodeRowsPayload($request->getRaw('rows', $request->get('rows')));
			$originalPayload = $this->decodeRowsPayload($request->getRaw('original', $request->get('original')));
			if (!$rowsPayload) {
				throw new \RuntimeException('Nie przesłano zmian do zapisania.');
			}

			$changes = $this->buildRowChanges($rowsPayload, $originalPayload);
			if (!$changes) {
				if ($isAjax) {
					$response->setResult(['updated' => 0]);
					$response->emit();
					return;
				}
				$this->setFlashMessage('ImportManagerRetrySuccess', 0);
				$this->redirectBack($batchId);
				return;
			}

			$updated = $this->retryManager->updateRows($batchId, $changes);
			if ($isAjax) {
				$response->setResult(['updated' => $updated]);
				$response->emit();
				return;
			}
			$this->setFlashMessage('ImportManagerRetrySuccess', $updated);
			$this->redirectBack($batchId);
		} catch (\Throwable $exception) {
			\App\Log\Log::error('ImportManager retry update failed: ' . $exception->getMessage(), 'ImportManager');
			if ($isAjax) {
				$response->setError(500, $exception->getMessage());
				$response->emit();
				return;
			}
			$this->setFlashMessage('ImportManagerRetryError', $exception->getMessage());
			$this->redirectBack((int) $request->get('batch_id'));
			return;
		}
	}

	/**
	 * @param mixed $payload
	 */
	private function decodeRowsPayload($payload): array
	{
		if (is_string($payload) && $payload !== '') {
			$decoded = \App\Utils\Json::decode($payload);
			return is_array($decoded) ? $decoded : [];
		}
		return is_array($payload) ? $payload : [];
	}

	private function buildRowChanges(array $rows, array $original): array
	{
		$changes = [];
		foreach ($rows as $rowNumber => $fields) {
			if (is_array($fields) && $fields) {
				$rowId = (int) $rowNumber;
				if ($rowId <= 0) {
					continue;
				}
				$originalFields = $original[$rowNumber] ?? [];
				$diff = [];
				foreach ($fields as $fieldName => $value) {
					$current = $value;
					$previous = $originalFields[$fieldName] ?? null;
					if ((string) $current !== (string) $previous) {
						$diff[$fieldName] = $current;
					}
				}
				if ($diff) {
					$changes[] = [
						'rowNumber' => $rowId,
						'values' => $diff,
					];
				}
			} elseif (is_array($rows[$rowNumber]) && isset($rows[$rowNumber]['rowNumber'], $rows[$rowNumber]['values'])) {
				// Already in API format (JSON from legacy UI)
				$changes[] = $rows[$rowNumber];
			}
		}
		return $changes;
	}

	private function redirectBack(int $batchId): void
	{
		header('Location: index.php?module=ImportManager&view=Retry&batch_id=' . $batchId);
		exit;
	}

	/**
	 * Bezpieczne ustawienie flash message - działa z Yii lub bezpośrednio z $_SESSION
	 */
	private function setFlashMessage(string $key, $value): void
	{
		if (class_exists('\Yii') && isset(\Yii::$app) && \Yii::$app !== null && isset(\Yii::$app->session)) {
			\Yii::$app->session->setFlash($key, $value);
		} else {
			// Fallback: użyj bezpośrednio $_SESSION z logiką flash messages
			if (session_status() === PHP_SESSION_ACTIVE) {
				$flashParam = '__flash';
				$counters = isset($_SESSION[$flashParam]) ? $_SESSION[$flashParam] : [];
				$counters[$key] = -1; // Oznacz jako flash message do usunięcia po odczytaniu
				$_SESSION[$key] = $value;
				$_SESSION[$flashParam] = $counters;
			}
		}
	}
}

