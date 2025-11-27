<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * Simple delimiter detector for CSV files.
 */

declare(strict_types=1);

namespace App\Modules\ImportManager\Parsers;

class DelimiterDetector
{
	private const DEFAULT_DELIMITERS = [',', ';', "\t", '|'];

	public function detect(string $filePath, array $candidates = []): string
	{
		$candidates = $candidates ?: self::DEFAULT_DELIMITERS;
		$handle = new \SplFileObject($filePath, 'r');
		$handle->setFlags(\SplFileObject::READ_AHEAD | \SplFileObject::SKIP_EMPTY);

		$scores = [];
		$linesChecked = 0;
		foreach ($handle as $line) {
			if ($line === false) {
				continue;
			}

			$line = (string) $line;
			foreach ($candidates as $delimiter) {
				$fields = str_getcsv($line, $delimiter);
				$count = count($fields);
				if ($count <= 1) {
					continue;
				}
				$scores[$delimiter] = ($scores[$delimiter] ?? 0) + $count;
			}

			if (++$linesChecked >= 5) {
				break;
			}
		}

		if (!$scores) {
			return ',';
		}

		arsort($scores);
		return array_key_first($scores);
	}
}

