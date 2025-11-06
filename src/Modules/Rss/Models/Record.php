<?php

/**
 * RSS Record Model
 *
 * @package   Modules\Rss\Models
 * @author    bmankowski@gmail.com
 * @copyright FreeCRM Public License 1.1
 */

namespace App\Modules\Rss\Models;

/**
 * RSS Record Model Class
 */
class Record extends \App\Modules\Base\Models\Record
{
	/**
	 * Initialize RSS Feed cache directory
	 *
	 * @return void
	 */
	protected function initializeFeedCache(): void
	{
		if (!isset(\Feed::$cacheDir)) {
			\Feed::$cacheDir = 'cache/rss_cache';
		}
	}

	/**
	 * Get the id of the Record
	 *
	 * @return int|null Report Id
	 */
	public function getId()
	{
		return $this->get('rssid');
	}

	/**
	 * Set the id of the Record
	 *
	 * @param int $value Id value
	 *
	 * @return \App\Runtime\BaseModel
	 */
	public function setId($value)
	{
		return $this->set('rssid', $value);
	}

	/**
	 * Get the Name of the Record
	 *
	 * @return string
	 */
	public function getName()
	{
		return $this->get('rsstitle');
	}

	/**
	 * Get RSS fetched object
	 *
	 * @return mixed RSS Object
	 */
	public function getRssObject()
	{
		return $this->get('rss');
	}

	/**
	 * Set RSS Object
	 *
	 * @param object $rss RSS fetched object
	 *
	 * @return \App\Runtime\BaseModel
	 */
	public function setRssObject($rss)
	{
		return $this->set('rss', $rss->item);
	}

	/**
	 * Set RSS values
	 *
	 * @param object $rss RSS fetched object
	 *
	 * @return void
	 */
	public function setRssValues($rss): void
	{
		$this->set('rsstitle', $rss->title);
		$this->set('url', $rss->link);
	}

	/**
	 * Save the record
	 *
	 * @param string $url RSS feed URL
	 *
	 * @return int|false Record ID on success, false on failure
	 */
	public function saveRecord(string $url)
	{
		$title = $this->getName();
		if ($title === '') {
			$title = $url;
		}
		$db = \App\Db::getInstance();
		$insert = $db->createCommand()->insert('vtiger_rss', ['rssurl' => $url, 'rsstitle' => $title])->execute();

		if ($insert) {
			$id = (int) $db->getLastInsertID('vtiger_rss_rssid_seq');
			$this->setId($id);
			return $id;
		}

		return false;
	}

	/**
	 * Delete a record
	 *
	 * @return void
	 */
	public function delete(): void
	{
		$db = \App\Database\PearDatabase::getInstance();
		$recordId = $this->getId();

		$sql = 'DELETE FROM vtiger_rss where rssid = ?';
		$db->pquery($sql, [$recordId]);
	}

	/**
	 * Make a record default for an RSS record
	 *
	 * @return void
	 */
	public function makeDefault(): void
	{
		$db = \App\Database\PearDatabase::getInstance();
		$recordId = $this->getId();

		$sql = 'UPDATE vtiger_rss set starred = 0';
		$db->pquery($sql, []);

		$sql = 'UPDATE vtiger_rss set starred = 1 where rssid = ?';
		$db->pquery($sql, [$recordId]);
	}

	/**
	 * Get record instance by using id and moduleName
	 *
	 * @param int         $recordId            Record ID
	 * @param string|null $qualifiedModuleName Qualified module name
	 *
	 * @return static|false RecordModel instance or false
	 */
	public static function getInstanceById($recordId, $qualifiedModuleName = null)
	{
		$rowData = (new \App\Db\Query)->from('vtiger_rss')->where(['rssid' => $recordId])->one();

		if ($rowData) {
			$recordModel = new self();
			$recordModel->setData($rowData);
			$recordModel->setModule($qualifiedModuleName);
			$recordModel->initializeFeedCache();
			$rss = \Feed::loadRss($recordModel->get('rssurl'));
			$recordModel->setSenderInfo($rss->item);
			$recordModel->setRssValues($rss);
			$recordModel->setRssObject($rss);

			return $recordModel;
		}

		return false;
	}

	/**
	 * Set the sender address to the record
	 *
	 * @param array $rssItems RSS items reference
	 *
	 * @return void
	 */
	public function setSenderInfo(&$rssItems): void
	{
		foreach ($rssItems as $item) {
			$item->sender = $this->getName();
		}
	}

	/**
	 * Get clean record instance by using moduleName
	 *
	 * @param string $qualifiedModuleName Qualified module name
	 *
	 * @return static
	 */
	public static function getCleanInstance($qualifiedModuleName)
	{
		$recordModel = new self();
		return $recordModel->setModule($qualifiedModuleName);
	}

	/**
	 * Validate the RSS URL
	 *
	 * @param string $url RSS feed URL
	 *
	 * @return bool True if valid, false otherwise
	 */
	public function validateRssUrl(string $url): bool
	{
		try {
			$this->initializeFeedCache();
			$rss = \Feed::loadRss($url);
			if ($rss) {
				$this->setRssValues($rss);
				return true;
			}

			return false;
		} catch (\FeedException $ex) {
			return false;
		}
	}

	/**
	 * Get the default RSS
	 *
	 * @return void
	 */
	public function getDefaultRss(): void
	{
		$db = \App\Database\PearDatabase::getInstance();

		$result = $db->pquery('SELECT rssid FROM vtiger_rss where starred = 1', []);
		$recordId = $db->query_result($result, '0', 'rssid');
		if ($recordId) {
			$this->setId($recordId);
		} else {
			$result = $db->pquery('SELECT rssid FROM vtiger_rss', []);
			$recordId = $db->query_result($result, '0', 'rssid');
			$this->setId($recordId);
		}
	}
}
