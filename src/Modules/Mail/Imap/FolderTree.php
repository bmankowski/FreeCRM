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

namespace App\Modules\Mail\Imap;

use Webklex\PHPIMAP\Client;
use Webklex\PHPIMAP\Folder;

class FolderTree
{
	private const SENT_NAME_PRIORITY = [
		'Sent',
		'Wysłane',
		'Elementy wysłane',
		'Sent Items',
		'Sent Messages',
		'sent-mail',
		'sent',
	];

	/**
	 * @return array{folders: list<string>, folder_tree: list<array<string, mixed>>, suggested_sent: ?string}
	 */
	public static function fromClient(Client $client): array
	{
		$client->connect();
		try {
			return self::fetchFolderData($client);
		} finally {
			$client->disconnect();
		}
	}

	/**
	 * Requires an already-connected IMAP client.
	 *
	 * @return array{folders: list<string>, folder_tree: list<array<string, mixed>>, suggested_sent: ?string}
	 */
	public static function fetchFolderData(Client $client): array
	{
		$collection = $client->getFolders(false);

		$flat = [];
		foreach ($collection as $folder) {
			$fullName = self::folderFullName($folder);
			if ($fullName !== '') {
				$flat[] = $fullName;
			}
		}
		$flat = array_values(array_unique($flat));

		return [
			'folders' => $flat,
			'folder_tree' => self::buildTreeFromFlat($collection),
			'suggested_sent' => self::suggestSentFolder($flat),
		];
	}

	/**
	 * @param list<string> $folderNames
	 */
	public static function suggestSentFolder(array $folderNames): ?string
	{
		$byLower = [];
		foreach ($folderNames as $name) {
			$byLower[strtolower($name)] = $name;
		}
		foreach (self::SENT_NAME_PRIORITY as $candidate) {
			$key = strtolower($candidate);
			if (isset($byLower[$key])) {
				return $byLower[$key];
			}
		}
		foreach ($folderNames as $name) {
			$lower = strtolower($name);
			if (str_contains($lower, 'sent') || str_contains($lower, 'wysłan') || str_contains($lower, 'wyslan')) {
				return $name;
			}
		}

		return null;
	}

	/**
	 * @param iterable<Folder> $collection
	 * @return list<array<string, mixed>>
	 */
	private static function buildTreeFromFlat(iterable $collection): array
	{
		/** @var array<string, array<string, mixed>> $roots */
		$roots = [];

		foreach ($collection as $folder) {
			$fullName = self::folderFullName($folder);
			if ($fullName === '') {
				continue;
			}

			$delimiter = $folder->delimiter !== '' ? $folder->delimiter : '.';
			$parts = explode($delimiter, $fullName);
			$node = &$roots;
			$built = '';

			foreach ($parts as $i => $part) {
				$built = $built === '' ? $part : $built . $delimiter . $part;
				if (!isset($node[$part])) {
					$node[$part] = [
						'name' => $part,
						'path' => $built,
						'full_name' => $built,
						'children' => [],
					];
				}
				if ($i === count($parts) - 1) {
					$node[$part]['path'] = $folder->path ?: $fullName;
					$node[$part]['full_name'] = $fullName;
				}
				$node = &$node[$part]['children'];
			}
			unset($node);
		}

		return self::normalizeTreeNodes($roots);
	}

	/**
	 * @param array<string, array<string, mixed>> $nodes
	 * @return list<array<string, mixed>>
	 */
	private static function normalizeTreeNodes(array $nodes): array
	{
		$result = [];
		foreach ($nodes as $node) {
			$children = $node['children'] ?? [];
			$node['children'] = is_array($children) ? self::normalizeTreeNodes($children) : [];
			$result[] = $node;
		}

		return $result;
	}

	private static function folderFullName(Folder $folder): string
	{
		return $folder->full_name ?: $folder->name;
	}
}
