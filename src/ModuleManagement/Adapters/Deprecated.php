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
 * Deprecated adapter class.
 * 
 * Backward compatibility adapter for vtlib\Deprecated.
 * Implements deprecated functionality directly.
 * 
 * @deprecated This class contains deprecated functionality
 */
class Deprecated
{
	/**
	 * Create module meta file
	 * @return void
	 */
	public static function createModuleMetaFile()
	{
		$adb = \App\Database\PearDatabase::getInstance();
		$result = $adb->pquery('select * from vtiger_tab');
		$result_array = $seq_array = $ownedby_array = [];

		while ($row = $adb->getRow($result)) {
			$tabid = (int) $row['tabid'];
			$tabname = $row['name'];
			$presence = (int) $row['presence'];
			$ownedby = (int) $row['ownedby'];
			$result_array[$tabname] = $tabid;
			$seq_array[$tabid] = $presence;
			$ownedby_array[$tabid] = $ownedby;
		}
		
		//Constructing the actionname=>actionid array
		$actionid_array = [];
		$result = $adb->pquery('select * from vtiger_actionmapping');
		while ($row = $adb->getRow($result)) {
			$actionname = $row['actionname'];
			$actionid = (int) $row['actionid'];
			$actionid_array[$actionname] = $actionid;
		}

		//Constructing the actionid=>actionname array with securitycheck=0
		$actionname_array = [];
		$result = $adb->pquery('select * from vtiger_actionmapping where securitycheck=0');
		while ($row = $adb->getRow($result)) {
			$actionname = $row['actionname'];
			$actionid = (int) $row['actionid'];
			$actionname_array[$actionid] = $actionname;
		}

		$filename = 'user_privileges/tabdata.php';

		if (file_exists($filename)) {
			if (is_writable($filename)) {
				if (!$handle = fopen($filename, 'w+')) {
					throw new \App\Exceptions\NoPermitted("Cannot open file ($filename)");
				}

				$newbuf = "<?php\n";
				$newbuf .= "\$tab_info_array=" . Functions::varExportMin($result_array) . ";\n";
				$newbuf .= "\$tab_seq_array=" . Functions::varExportMin($seq_array) . ";\n";
				$newbuf .= "\$tab_ownedby_array=" . Functions::varExportMin($ownedby_array) . ";\n";
				$newbuf .= "\$action_id_array=" . Functions::varExportMin($actionid_array) . ";\n";
				$newbuf .= "\$action_name_array=" . Functions::varExportMin($actionname_array) . ";\n";
				$tabdata = [
					'tabId' => $result_array,
					'tabPresence' => $seq_array,
					'tabOwnedby' => $ownedby_array,
					'actionId' => $actionid_array,
					'actionName' => $actionname_array,
				];
				$newbuf .= 'return ' . Functions::varExportMin($tabdata) . ";\n";
				fputs($handle, $newbuf);
				fclose($handle);
			} else {
				\App\Log\Log::error("The file $filename is not writable");
			}
		} else {
			\App\Log\Log::error("The file $filename does not exist");
		}
	}

	/**
	 * Check file access for inclusion
	 * @param string $filepath
	 * @return void
	 * @throws \App\Exceptions\AppException
	 */
	public static function checkFileAccessForInclusion($filepath)
	{
		$unsafeDirectories = array('storage', 'cache', 'test');
		$realfilepath = realpath($filepath);

		/** Replace all \\ with \ first */
		if ($realfilepath === false) {
			\App\Log\Log::error(__METHOD__ . '(' . $filepath . ') - File does not exist');
			throw new \App\Exceptions\AppException('File does not exist: ' . $filepath);
		}
		$realfilepath = str_replace('\\\\', '\\', $realfilepath);
		$rootdirpath = str_replace('\\\\', '\\', ROOT_DIRECTORY . DIRECTORY_SEPARATOR);

		/** Replace all \ with / now */
		$realfilepath = str_replace('\\', '/', $realfilepath);
		$rootdirpath = str_replace('\\', '/', $rootdirpath);

		$relativeFilePath = str_replace($rootdirpath, '', $realfilepath);
		$filePathParts = explode('/', $relativeFilePath);

		if (stripos($realfilepath, $rootdirpath) !== 0 || in_array($filePathParts[0], $unsafeDirectories)) {
			\App\Log\Log::error(__METHOD__ . '(' . $filepath . ') - Sorry! Attempt to access restricted file. realfilepath: ' . print_r($realfilepath, true));
			throw new \App\Exceptions\AppException('Sorry! Attempt to access restricted file.');
		}
	}

	/**
	 * Check file access for deletion
	 * @param string $filepath
	 * @return void
	 * @throws \App\Exceptions\AppException
	 */
	public static function checkFileAccessForDeletion($filepath)
	{
		$safeDirectories = array('storage', 'cache', 'test');
		$realfilepath = realpath($filepath);

		/** Replace all \\ with \ first */
		$realfilepath = str_replace('\\\\', '\\', $realfilepath);
		$rootdirpath = str_replace('\\\\', '\\', ROOT_DIRECTORY . DIRECTORY_SEPARATOR);

		/** Replace all \ with / now */
		$realfilepath = str_replace('\\', '/', $realfilepath);
		$rootdirpath = str_replace('\\', '/', $rootdirpath);

		$relativeFilePath = str_replace($rootdirpath, '', $realfilepath);
		$filePathParts = explode('/', $relativeFilePath);

		if (stripos($realfilepath, $rootdirpath) !== 0 || !in_array($filePathParts[0], $safeDirectories)) {
			\App\Log\Log::error(__METHOD__ . '(' . $filepath . ') - Sorry! Attempt to access restricted file. realfilepath: ' . print_r($realfilepath, true));
			throw new \App\Exceptions\AppException('Sorry! Attempt to access restricted file.');
		}
	}

	/**
	 * Get full name from query result
	 * @param resource $result Query result resource
	 * @param int $row_count Row number
	 * @param string $module Module name
	 * @return string Full name
	 */
	public static function getFullNameFromQResult($result, $row_count, $module)
	{
		$adb = \App\Database\PearDatabase::getInstance();
		$rowdata = $adb->query_result_rowdata($result, $row_count);
		$entity_field_info = \App\Utils\ModuleUtils::getEntityInfo($module);
		$fieldsName = $entity_field_info['fieldname'];
		$name = '';
		if ($rowdata != '' && count($rowdata) > 0) {
			$name = self::getCurrentUserEntityFieldNameDisplay($module, $fieldsName, $rowdata);
		}
		$name = Functions::textLength($name);
		return $name;
	}

	/**
	 * Get full name from array
	 * @param string $module Module name
	 * @param array $fieldValues Field values array
	 * @return string Full name
	 */
	public static function getFullNameFromArray($module, $fieldValues)
	{
		$entityInfo = \App\Utils\ModuleUtils::getEntityInfo($module);
		$fieldsName = $entityInfo['fieldname'];
		$displayName = self::getCurrentUserEntityFieldNameDisplay($module, $fieldsName, $fieldValues);
		return $displayName;
	}

	/**
	 * Get entity field name display for current user
	 * This function returns the entity field name for a given module; for e.g. for Contacts module it return concat(lastname, ' ', firstname)
	 * @param string $module Module name
	 * @param string $fieldsName Field name with respect to module (ex : 'Accounts' - 'accountname', 'Contacts' - 'lastname','firstname')
	 * @param array $fieldValues Array of fieldname and its value
	 * @return string Entity field name for the module
	 */
	public static function getCurrentUserEntityFieldNameDisplay($module, $fieldsName, $fieldValues)
	{
		if (strpos($fieldsName, ',') === false) {
			return $fieldValues[$fieldsName];
		} else {
			$accessibleFieldNames = [];
			foreach (explode(',', $fieldsName) as $field) {
				if ($module === 'Users' || \App\Fields\Field::getColumnPermission($module, $field)) {
					$accessibleFieldNames[] = $fieldValues[$field];
				}
			}
			if (count($accessibleFieldNames) > 0) {
				return implode(' ', $accessibleFieldNames);
			}
		}
		return '';
	}

	/**
	 * Get block ID by tab ID and label
	 * @param int $tabid Tab ID
	 * @param string $label Block label
	 * @return string Block ID
	 */
	public static function getBlockId($tabid, $label)
	{
		$adb = \App\Database\PearDatabase::getInstance();
		$query = "select blockid from vtiger_blocks where tabid=? and blocklabel = ?";
		$result = $adb->pquery($query, array($tabid, $label));
		$noofrows = $adb->num_rows($result);

		$blockid = '';
		if ($noofrows == 1) {
			$blockid = $adb->query_result($result, 0, "blockid");
		}
		return $blockid;
	}

	/**
	 * Get module translation strings
	 * @param string $language Language code
	 * @param string $module Module name
	 * @return array Translation strings
	 */
	public static function getModuleTranslationStrings($language, $module)
	{
		static $cachedModuleStrings = [];

		if (!empty($cachedModuleStrings[$module])) {
			return $cachedModuleStrings[$module];
		}
		$newStrings = \App\Runtime\Vtiger_Language_Handler::getModuleStringsFromFile($language, $module);
		$cachedModuleStrings[$module] = $newStrings['languageStrings'];

		return $cachedModuleStrings[$module];
	}

	/**
	 * Get ID of custom view named "All" for a module
	 * @param string $module Module name
	 * @return string Custom view ID
	 */
	public static function getIdOfCustomViewByNameAll($module)
	{
		$adb = \App\Database\PearDatabase::getInstance();

		static $cvidCache = [];
		if (!isset($cvidCache[$module])) {
			$qry_res = $adb->pquery("select cvid from vtiger_customview where viewname='All' and entitytype=?", array($module));
			$cvid = $adb->query_result($qry_res, 0, "cvid");
			$cvidCache[$module] = $cvid;
		}
		return isset($cvidCache[$module]) ? $cvidCache[$module] : '0';
	}

	/**
	 * Get Smarty compiled template file
	 * @param string $template_file Template file name
	 * @param string|null $path Path to search (defaults to cache/templates_c/)
	 * @return string|null Compiled file path or null if not found
	 */
	public static function getSmartyCompiledTemplateFile($template_file, $path = null)
	{
		if ($path === null) {
			$path = ROOT_DIRECTORY . '/cache/templates_c/';
		}
		$mydir = @opendir($path);
		$compiled_file = null;
		while (false !== ($file = readdir($mydir)) && $compiled_file === null) {
			if ($file != '.' && $file != '..' && $file != '.svn') {
				if (is_dir($path . $file)) {
					chdir('.');
					$compiled_file = self::getSmartyCompiledTemplateFile($template_file, $path . $file . '/');
				} else {
					// Check if the file name matches the required template file name
					if (strripos($file, $template_file . '.php') == (strlen($file) - strlen($template_file . '.php'))) {
						$compiled_file = $path . $file;
					}
				}
			}
		}
		@closedir($mydir);
		return $compiled_file;
	}

	/**
	 * Check file access
	 * @param string $filepath
	 * @return void
	 * @throws \App\Exceptions\AppException
	 */
	public static function checkFileAccess($filepath)
	{
		if (!self::isFileAccessible($filepath)) {
			$realfilepath = realpath($filepath);
			\App\Log\Log::error(__METHOD__ . '(' . $filepath . ') - Sorry! Attempt to access restricted file. realfilepath: ' . print_r($realfilepath, true));
			throw new \App\Exceptions\AppException('Sorry! Attempt to access restricted file.');
		}
	}

	/**
	 * Get settings block ID by label
	 * @param string $label Settings label
	 * @return string Block ID
	 */
	public static function getSettingsBlockId($label)
	{
		$adb = \App\Database\PearDatabase::getInstance();
		$blockid = '';
		$query = "select blockid from vtiger_settings_blocks where label = ?";
		$result = $adb->pquery($query, array($label));
		$noofrows = $adb->num_rows($result);
		if ($noofrows == 1) {
			$blockid = $adb->query_result($result, 0, "blockid");
		}
		return $blockid;
	}

	/**
	 * Get SQL for name in display format
	 * @param array $input Input array
	 * @param string $module Module name
	 * @param string $glue Glue string (default: ' ')
	 * @return string SQL CONCAT string
	 */
	public static function getSqlForNameInDisplayFormat($input, $module, $glue = ' ')
	{
		$entityFieldInfo = \App\Utils\ModuleUtils::getEntityInfo($module);
		$fieldsName = $entityFieldInfo['fieldnameArr'];
		if (is_array($fieldsName)) {
			$formattedNameList = [];
			foreach ($fieldsName as &$value) {
				$formattedNameList[] = $input[$value];
			}
			$formattedNameListString = implode(",'" . $glue . "',", $formattedNameList);
		} else {
			$formattedNameListString = $input[$fieldsName];
		}
		$sqlString = "CONCAT(" . $formattedNameListString . ")";
		return $sqlString;
	}

	/**
	 * Return app list strings language
	 * @param string $language Language code
	 * @param string $module Module name (default: 'Vtiger')
	 * @return array Language strings
	 */
	public static function return_app_list_strings_language($language, $module = 'Vtiger')
	{
		$strings = \App\Runtime\Vtiger_Language_Handler::getModuleStringsFromFile($language, $module);
		return $strings['languageStrings'];
	}

	/**
	 * Check if file is accessible
	 * @param string $filepath
	 * @return bool
	 */
	public static function isFileAccessible($filepath)
	{
		$realfilepath = realpath($filepath);
		if ($realfilepath === false) {
			return false;
		}

		/** Replace all \\ with \ first */
		$realfilepath = str_replace('\\\\', '\\', $realfilepath);
		$rootdirpath = str_replace('\\\\', '\\', ROOT_DIRECTORY . DIRECTORY_SEPARATOR);

		/** Replace all \ with / now */
		$realfilepath = str_replace('\\', '/', $realfilepath);
		$rootdirpath = str_replace('\\', '/', $rootdirpath);

		if (stripos($realfilepath, $rootdirpath) !== 0) {
			return false;
		}
		return true;
	}
}

