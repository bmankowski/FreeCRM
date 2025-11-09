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

namespace vtlib;

/**
 * Provides API to handle custom links.
 *
 * Backward compatibility adapter for vtlib\Link.
 */
class Link
{
	const IGNORE_MODULE = -1;

	public $tabid;
	public $linkid;
	public $linktype;
	public $linklabel;
	public $linkurl;
	public $linkicon;
	public $glyphicon;
	public $sequence;
	public $status = false;
	public $handler_path;
	public $handler_class;
	public $handler;
	public $params;

	/** Cache (Record) the schema changes to improve performance */
	private static array $__cacheSchemaChanges = [];

	/**
	 * Initialize this instance.
	 *
	 * @param array $valuemap
	 *
	 * @return void
	 */
	public function initialize(array $valuemap): void
	{
		foreach ($valuemap as $key => $value) {
			if ($key === 'linkurl' || $key === 'linkicon') {
				$this->$key = \App\Utils\ListViewUtils::decodeHtml($value);
			} else {
				$this->$key = $value;
			}
		}
	}

	/**
	 * Get module name.
	 *
	 * @return string|false
	 */
	public function module()
	{
		if (!empty($this->tabid)) {
			return \App\Module::getModuleName($this->tabid);
		}
		return false;
	}

	/**
	 * Add link given module.
	 *
	 * @param int         $tabid
	 * @param string      $type
	 * @param string      $label
	 * @param string      $url
	 * @param string      $iconpath
	 * @param int         $sequence
	 * @param array|null  $handlerInfo
	 * @param string|null $linkParams
	 *
	 * @return void
	 */
	public static function addLink($tabid, $type, $label, $url, $iconpath = '', $sequence = 0, $handlerInfo = null, $linkParams = null): void
	{
		$db = \App\Db::getInstance();
		$checkres = false;
		if ($tabid != 0) {
			$checkres = (new \App\Db\Query())->from('vtiger_links')
				->where(['tabid' => $tabid, 'linktype' => $type, 'linkurl' => $url, 'linkicon' => $iconpath, 'linklabel' => $label])
				->exists();
		}
		if ($tabid == 0 || !$checkres) {
			$params = [
				'tabid' => $tabid,
				'linktype' => $type,
				'linklabel' => $label,
				'linkurl' => $url,
				'linkicon' => $iconpath,
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
			$db->createCommand()->insert('vtiger_links', $params)->execute();
			self::log("Adding Link ($type - $label) ... DONE");
		}
	}

	/**
	 * Delete link of the module.
	 *
	 * @param int    $tabid
	 * @param string $type
	 * @param string $label
	 * @param string $url
	 *
	 * @return void
	 */
	public static function deleteLink($tabid, $type, $label, $url = false): void
	{
		$db = \App\Db::getInstance();
		if ($url) {
			$db->createCommand()->delete('vtiger_links', [
				'tabid' => $tabid,
				'linktype' => $type,
				'linklabel' => $label,
				'linkurl' => $url,
			])->execute();
			self::log("Deleting Link ($type - $label - $url) ... DONE");
		} else {
			$db->createCommand()->delete('vtiger_links', [
				'tabid' => $tabid,
				'linktype' => $type,
				'linklabel' => $label,
			])->execute();
			self::log("Deleting Link ($type - $label) ... DONE");
		}
	}

	/**
	 * Delete all links related to module.
	 *
	 * @param int $tabid
	 *
	 * @return void
	 */
	public static function deleteAll($tabid): void
	{
		\App\Db::getInstance()->createCommand()->delete('vtiger_links', ['tabid' => $tabid])->execute();
		self::log('Deleting Links ... DONE');
	}

	/**
	 * Get all the links related to module.
	 *
	 * @param int $tabid
	 *
	 * @return array
	 */
	public static function getAll($tabid): array
	{
		return self::getAllByType($tabid);
	}

	/**
	 * Get all the link related to module based on type.
	 *
	 * @param int        $tabid
	 * @param mixed      $type
	 * @param array|bool $parameters
	 *
	 * @return array
	 */
	public static function getAllByType($tabid, $type = false, $parameters = false): array
	{
		$currentUser = \App\Modules\Users\Models\Record::getCurrentUserModel();
		if (\App\Cache\Cache::has('AllLinks', 'ByType')) {
			$rows = \App\Cache\Cache::get('AllLinks', 'ByType');
		} else {
			$linksFromDb = (new \App\Db\Query())->from('vtiger_links')->all();
			$rows = [];
			foreach ($linksFromDb as $row) {
				$rows[$row['tabid']][$row['linktype']][] = $row;
			}
			\App\Cache\Cache::save('AllLinks', 'ByType', $rows);
		}

		$multitype = false;
		$links = [];
		if ($type !== false) {
			if (is_array($type)) {
				$multitype = true;
				if ($tabid === self::IGNORE_MODULE) {
					$permittedTabIdList = \App\Utils\UserInfoUtil::getPermittedModuleIdList();
					if (!empty($permittedTabIdList)) {
						$permittedTabIdList[] = 0;
						foreach ($permittedTabIdList as $moduleId) {
							foreach ($type as $typ) {
								if (isset($rows[$moduleId][$typ])) {
									foreach ($rows[$moduleId][$typ] as $data) {
										$links[] = $data;
									}
								}
							}
						}
					}
				} else {
					foreach ($type as $typeLink) {
						if (isset($rows[0][$typeLink])) {
							foreach ($rows[0][$typeLink] as $data) {
								$links[] = $data;
							}
						}
						if (isset($rows[$tabid][$typeLink])) {
							foreach ($rows[$tabid][$typeLink] as $data) {
								$links[] = $data;
							}
						}
					}
				}
			} else {
				if ($tabid === self::IGNORE_MODULE) {
					foreach ($rows as $row) {
						if (isset($row[$type])) {
							foreach ($row[$type] as $data) {
								$links[] = $data;
							}
						}
					}
				} else {
					if (isset($rows[0][$type])) {
						foreach ($rows[0][$type] as $data) {
							$links[] = $data;
						}
					}
					if (isset($rows[$tabid][$type])) {
						foreach ($rows[$tabid][$type] as $data) {
							$links[] = $data;
						}
					}
				}
			}
		} else {
			foreach ($rows[$tabid] as $linkType) {
				foreach ($linkType as $data) {
					$links[] = $data;
				}
			}
		}

		$strtemplate = new \Vtiger_StringTemplate();
		if ($parameters) {
			foreach ($parameters as $key => $value) {
				$strtemplate->assign($key, $value);
			}
		}

		$instances = [];
		if ($multitype) {
			foreach ($type as $t) {
				$instances[$t] = [];
			}
		}
		foreach ($links as $row) {
			$instance = new self();
			$instance->initialize($row);
			if (!empty($row['handler_path']) && \vtlib\Deprecated::isFileAccessible($row['handler_path'])) {
				\vtlib\Deprecated::checkFileAccessForInclusion($row['handler_path']);
				require_once $row['handler_path'];
				$linkData = new LinkData($instance, \App\User\CurrentUser::get(), $_REQUEST);
				$ignore = call_user_func([$row['handler_class'], $row['handler']], $linkData);
				if (!$ignore) {
					self::log('Ignoring Link ... ' . var_export($row, true));
					continue;
				}
			}
			if ($parameters) {
				$instance->linkurl = $strtemplate->merge($instance->linkurl);
				$instance->linkicon = $strtemplate->merge($instance->linkicon);
			}
			if ($multitype) {
				$instances[$instance->linktype][] = $instance;
			} else {
				$instances[$instance->linktype] = $instance;
			}
		}
		return $instances;
	}

	/**
	 * Extract the links of module for export.
	 *
	 * @param int $tabid
	 *
	 * @return array
	 */
	public static function getAllForExport($tabid): array
	{
		$dataReader = (new \App\Db\Query())->from('vtiger_links')
				->where(['tabid' => $tabid])
				->createCommand()->query();
		$links = [];
		while ($row = $dataReader->read()) {
			$instance = new self();
			$instance->initialize($row);
			$links[] = $instance;
		}
		return $links;
	}

	/**
	 * Helper function to log messages.
	 *
	 * @param string $message
	 * @param bool   $delimit
	 *
	 * @return void
	 */
	public static function log($message, $delimit = true): void
	{
		Utils::Log($message, $delimit);
	}

	/**
	 * Checks whether the user is admin or not.
	 *
	 * @param LinkData $linkData
	 *
	 * @return bool
	 */
	public static function isAdmin(LinkData $linkData): bool
	{
		$user = $linkData->getUser();
		return $user->is_admin == 'on' || $user->column_fields['is_admin'] == 'on';
	}
}

