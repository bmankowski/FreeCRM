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

namespace App\ModuleManagement\Services;

use App\ModuleManagement\Models;

/**
 * LinkService class.
 * 
 * Service for custom link operations.
 */
class LinkService
{
	/** @var \App\Db\Db Database instance */
	private $db;

	/**
	 * Constructor.
	 * 	 * @param \App\Db\Db $db
	 */
	public function __construct(\App\Db\Db $db)
	{
		$this->db = $db;
	}

	/**
	 * Add custom link for a module.
	 * 	 * @param int $moduleId Module ID
	 * @param string $type Link type (e.g., 'DETAILVIEW', 'LISTVIEW')
	 * @param string $label Link label
	 * @param string $url Link URL
	 * @param string $icon Icon path
	 * @param int $sequence Sequence number
	 * @param array|null $handlerInfo Handler information ['path', 'class', 'method']
	 * @param string|null $linkParams Link parameters
	 * @return void
	 */
	public function addLink(int $moduleId, string $type, string $label, string $url, string $icon = '', int $sequence = 0, ?array $handlerInfo = null, ?string $linkParams = null): void
	{
		if ($moduleId != 0) {
			$checkres = (new \App\Db\Query())
				->from('vtiger_links')
				->where([
					'tabid' => $moduleId,
					'linktype' => $type,
					'linkurl' => $url,
					'linkicon' => $icon,
					'linklabel' => $label
				])
				->exists();
		}

		if ($moduleId == 0 || !$checkres) {
			$params = [
				'tabid' => $moduleId,
				'linktype' => $type,
				'linklabel' => $label,
				'linkurl' => $url,
				'linkicon' => $icon,
				'sequence' => (int) $sequence,
			];

			if (!empty($handlerInfo)) {
				$params['handler_path'] = $handlerInfo['path'];
				$params['handler_class'] = $handlerInfo['class'];
				$params['handler'] = $handlerInfo['method'];
			}

			if (!empty($linkParams)) {
				$params['params'] = $linkParams;
			}

			$this->db->createCommand()->insert('vtiger_links', $params)->execute();
		}
	}

	/**
	 * Delete all links for a module.
	 * 	 * @param int $moduleId Module ID
	 * @return void
	 */
	public function deleteAll(int $moduleId): void
	{
		$this->db->createCommand()
			->delete('vtiger_links', ['tabid' => $moduleId])
			->execute();
	}

	/**
	 * Export custom links to XML.
	 * 	 * @param Models\Module $module Module instance
	 * @param resource $manifestHandle Manifest file handle
	 * @return void
	 */
	public function exportToXML(Models\Module $module, $manifestHandle): void
	{
		$customlinks = $this->getAllForExport($module->getId());
		if (empty($customlinks)) {
			return;
		}

		$this->writeNode($manifestHandle, 'customlinks', '', true);
		foreach ($customlinks as $customlink) {
			$this->writeNode($manifestHandle, 'customlink', '', true);
			$this->writeNode($manifestHandle, 'linktype', $customlink['linktype']);
			$this->writeNode($manifestHandle, 'linklabel', $customlink['linklabel']);
			$this->writeNode($manifestHandle, 'linkurl', '<![CDATA[' . $customlink['linkurl'] . ']]>');
			$this->writeNode($manifestHandle, 'linkicon', '<![CDATA[' . $customlink['linkicon'] . ']]>');
			$this->writeNode($manifestHandle, 'sequence', $customlink['sequence']);
			if (!empty($customlink['handler_path'])) {
				$this->writeNode($manifestHandle, 'handler_path', '<![CDATA[' . $customlink['handler_path'] . ']]>');
			}
			if (!empty($customlink['handler_class'])) {
				$this->writeNode($manifestHandle, 'handler_class', '<![CDATA[' . $customlink['handler_class'] . ']]>');
			}
			if (!empty($customlink['handler'])) {
				$this->writeNode($manifestHandle, 'handler', '<![CDATA[' . $customlink['handler'] . ']]>');
			}
			$this->writeNode($manifestHandle, 'customlink', '', false);
		}
		$this->writeNode($manifestHandle, 'customlinks', '', false);
	}

	/**
	 * Get all links for export.
	 * 	 * @param int $moduleId Module ID
	 * @return array Array of link data
	 */
	private function getAllForExport(int $moduleId): array
	{
		$dataReader = (new \App\Db\Query())
			->from('vtiger_links')
			->where(['tabid' => $moduleId])
			->createCommand()
			->query();

		$links = [];
		while ($row = $dataReader->read()) {
			$link = [
				'linktype' => $row['linktype'],
				'linklabel' => $row['linklabel'],
				'linkurl' => \App\Utils\ListViewUtils::decodeHtml($row['linkurl']),
				'linkicon' => \App\Utils\ListViewUtils::decodeHtml($row['linkicon']),
				'sequence' => $row['sequence'],
			];

			if (!empty($row['handler_path'])) {
				$link['handler_path'] = $row['handler_path'];
			}
			if (!empty($row['handler_class'])) {
				$link['handler_class'] = $row['handler_class'];
			}
			if (!empty($row['handler'])) {
				$link['handler'] = $row['handler'];
			}

			$links[] = $link;
		}

		return $links;
	}

	/**
	 * Write XML node to manifest handle.
	 * 	 * @param resource $handle File handle
	 * @param string $node Node name
	 * @param mixed $value Node value
	 * @param bool $open Whether to open or close node
	 * @return void
	 */
	private function writeNode($handle, string $node, $value = '', bool $open = true): void
	{
		if ($open) {
			if ($value !== '') {
				fwrite($handle, "<$node>" . htmlspecialchars((string) $value, ENT_XML1, 'UTF-8') . "</$node>\n");
			} else {
				fwrite($handle, "<$node>\n");
			}
		} else {
			fwrite($handle, "</$node>\n");
		}
	}
}





