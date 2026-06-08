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

namespace App\Modules\LinkAction\Services;

class QueueHttpClient
{
	/**
	 * @return array{status: int, body: string, error: string}
	 */
	public function request(string $method, string $url, string $apiKey, int $timeoutSeconds): array
	{
		$curl = curl_init();
		if ($curl === false) {
			return ['status' => 0, 'body' => '', 'error' => 'curl_init failed'];
		}

		$method = strtoupper($method);
		$headers = ['X-LinkAction-Pull-Key: ' . $apiKey];
		$postFields = null;
		if ($method === 'GET') {
			$headers[] = 'Accept: application/x-ndjson';
		} elseif ($method === 'POST') {
			$headers[] = 'Content-Type: application/json';
			$postFields = '{}';
		}

		$options = [
			CURLOPT_URL => $url,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_CUSTOMREQUEST => $method,
			CURLOPT_HTTPHEADER => $headers,
			CURLOPT_TIMEOUT => max(1, $timeoutSeconds),
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_SSL_VERIFYPEER => true,
			CURLOPT_SSL_VERIFYHOST => 2,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_MAXREDIRS => 3,
		];
		if ($postFields !== null) {
			$options[CURLOPT_POSTFIELDS] = $postFields;
		}

		curl_setopt_array($curl, $options);

		$body = curl_exec($curl);
		$status = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
		$error = curl_error($curl);
		curl_close($curl);

		if ($body === false) {
			$body = '';
		}

		return [
			'status' => $status,
			'body' => (string) $body,
			'error' => $error,
		];
	}
}
