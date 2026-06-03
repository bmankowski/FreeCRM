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
		$collection = $client->getFolders(true);
		$tree = [];
		$flat = [];
		foreach ($collection as $folder) {
			$tree[] = self::folderNode($folder);
			self::collectFlat($folder, $flat);
		}
		$client->disconnect();

		return [
			'folders' => $flat,
			'folder_tree' => $tree,
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

	private static function folderNode(Folder $folder): array
	{
		$children = [];
		if ($folder->hasChildren()) {
			foreach ($folder->children as $child) {
				$children[] = self::folderNode($child);
			}
		}

		return [
			'name' => $folder->name,
			'path' => $folder->path,
			'full_name' => $folder->full_name,
			'children' => $children,
		];
	}

	/**
	 * @param list<string> $flat
	 */
	private static function collectFlat(Folder $folder, array &$flat): void
	{
		$flat[] = $folder->name;
		if ($folder->hasChildren()) {
			foreach ($folder->children as $child) {
				self::collectFlat($child, $flat);
			}
		}
	}
}
