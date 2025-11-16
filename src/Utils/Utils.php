<?php
/**
 * FreeCRM General Utilities
 */

namespace App\Utils;

class Utils
{
	/**
	 * Function to get entity name for a given module and IDs
	 * @param string $module Module name
	 * @param mixed $ids Entity IDs
	 * @param bool $compute Whether to compute labels
	 * @return mixed Entity names
	 */
	public static function getEntityName($module, $ids, $compute = true)
	{
		if ($module == 'Users' || $module == 'Groups') {
			return \App\Fields\Owner::getLabel($ids);
		} elseif ($compute) {
			return \App\Record::computeLabels($module, $ids);
		} else {
			return \App\Record::getLabel($ids);
		}
	}


	/** Function to return a full name
	 * @param $row -- row:: Type integer
	 * @param $first_column -- first column:: Type string
	 * @param $last_column -- last column:: Type string
	 * @returns $fullname -- fullname:: Type string
	 *
	 */
	public static function return_name(&$row, $first_column, $last_column)
	{

		\App\Log::trace("Entering return_name(" . $row . "," . $first_column . "," . $last_column . ") method ...");
		$first_name = "";
		$last_name = "";
		$full_name = "";

		if (isset($row[$first_column])) {
			$first_name = stripslashes($row[$first_column]);
		}

		if (isset($row[$last_column])) {
			$last_name = stripslashes($row[$last_column]);
		}

		$full_name = $first_name;

		// If we have a first name and we have a last name
		if ($full_name != "" && $last_name != "") {
			// append a space, then the last name
			$full_name .= " " . $last_name;
		}
		// If we have no first name, but we have a last name
		else if ($last_name != "") {
			// append the last name without the space.
			$full_name .= $last_name;
		}

		\App\Log::trace("Exiting return_name method ...");
		return $full_name;
	}

	/** Function to get column fields for a given module
	 * @param $module -- module:: Type string
	 * @returns $column_fld -- column field :: Type array
	 *
	 */
	public static function getColumnFields($module)
	{

		\App\Log::trace('Entering getColumnFields(' . $module . ') method ...');

		// Lookup in cache for information
		$cachedModuleFields = \App\Utils\VTCacheUtils::lookupFieldInfo_Module($module);

		if ($cachedModuleFields === false) {
			$fieldsInfo = \vtlib\Functions:: getModuleFieldInfos($module);
			if (!empty($fieldsInfo)) {
				foreach ($fieldsInfo as $resultrow) {
					// Update information to cache for re-use
					\App\Utils\VTCacheUtils::updateFieldInfo(
						$resultrow['tabid'],
						$resultrow['fieldname'],
						$resultrow['fieldid'],
						$resultrow['fieldlabel'],
						$resultrow['columnname'],
						$resultrow['tablename'],
						$resultrow['uitype'],
						$resultrow['typeofdata'],
						$resultrow['presence']
					);
				}
			}
			// For consistency get information from cache
			$cachedModuleFields = \App\Utils\VTCacheUtils::lookupFieldInfo_Module($module);
		}

	if ($module == 'Calendar') {
		$cachedEventsFields = \App\Utils\VTCacheUtils::lookupFieldInfo_Module('Events');
		if (!$cachedEventsFields) {
			self::getColumnFields('Events');
			$cachedEventsFields = \App\Utils\VTCacheUtils::lookupFieldInfo_Module('Events');
		}

			if (!$cachedModuleFields) {
				$cachedModuleFields = $cachedEventsFields;
			} else {
				$cachedModuleFields = array_merge($cachedModuleFields, $cachedEventsFields);
			}
		}

		$column_fld = [];
		if ($cachedModuleFields) {
			foreach ($cachedModuleFields as $fieldinfo) {
				$column_fld[$fieldinfo['fieldname']] = '';
			}
		}

		\App\Log::trace("Exiting getColumnFields method ...");
		return $column_fld;
	}

	/** Function to get a userid for outlook
	 * @param $username -- username :: Type string
	 * @returns $user_id -- user id :: Type integer
	 */
	//outlook security
	public static function getUserId_Ol($username)
	{

		\App\Log::trace("Entering getUserId_Ol(" . $username . ") method ...");
		\App\Log::trace("in getUserId_Ol " . $username);
		if (\App\Cache\Cache::has('UserId', $username)) {
			return \App\Cache\Cache::get('UserId', $username);
		} else {
			$adb = \App\Database\PearDatabase::getInstance();
			$sql = "select id from vtiger_users where user_name=?";
			$result = $adb->pquery($sql, array($username));
			$num_rows = $adb->num_rows($result);
			if ($num_rows > 0) {
				$user_id = $adb->query_result($result, 0, "id");
			} else {
				$user_id = 0;
			}
			\App\Log::trace("Exiting getUserId_Ol method ...");
			\App\Cache\Cache::save('UserId', $username, $user_id);
			return $user_id;
		}
	}

	/** Function to get a action id for a given action name
	 * @param $action -- action name :: Type string
	 * @returns $actionid -- action id :: Type integer
	 */
	//outlook security

	public static function getActionid($action)
	{

		\App\Log::trace('Entering \App\Utils\Utils::getActionid(' . $action . ') method ...');

		if (empty($action)) {
			return null;
		}
		$actionid = \App\Cache\Cache::get('getActionid', $action);
		if ($actionid) {
			\App\Log::trace('Exiting getActionid method ... - ' . $actionid);
			return $actionid;
		}
		$actionIds = \App\Utils\ModuleUtils::getTabData('actionId');
		if (isset($actionIds[$action])) {
			$actionid = $actionIds[$action];
		}
		if (empty($actionid)) {
			$db = \App\Database\PearDatabase::getInstance();
			$query = 'select actionid from vtiger_actionmapping where actionname=?';
			$result = $db->pquery($query, [$action]);
			$actionid = $db->getSingleValue($result);
		}
		\App\Cache\Cache::save('getActionid', $action, $actionid);
		\App\Log::trace('Exiting getActionid method ... - ' . $actionid);
		return $actionid;
	}

	/** Function to get a action for a given action id
	 * @param $action id -- action id :: Type integer
	 * @returns $actionname-- action name :: Type string
	 */
	public static function getActionname($actionid)
	{

		\App\Log::trace('Entering getActionname(' . $actionid . ') method ...');
		$adb = \App\Database\PearDatabase::getInstance();

		$actionName = \App\Cache\Cache::get('getActionName', $actionid);
		if ($actionName) {
			\App\Log::trace('Exiting getActionname method ...');
			return $actionName;
		}
		if (file_exists('user_privileges/tabdata.php') && (filesize('user_privileges/tabdata.php') != 0)) {
			include('user_privileges/tabdata.php');
			$actionName = $action_name_array[$actionid];
		} else {
			$query = 'select actionname from vtiger_actionmapping where actionid=? and securitycheck=0';
			$result = $adb->pquery($query, array($actionid));
			$actionName = $adb->getSingleValue($result);
		}
		\App\Cache\Cache::save('getActionName', $actionid, $actionName);
		\App\Log::trace('Exiting getActionname method ...');
		return $actionName;
	}

	/** Function to get a user id or group id for a given entity
	 * @param $record -- entity id :: Type integer
	 * @returns $ownerArr -- owner id :: Type array
	 */
	public static function getRecordOwnerId($record)
	{

		\App\Log::trace("Entering \App\Utils\Utils::getRecordOwnerId($record) method ...");
		$ownerArr = [];

		$recordMetaData = \vtlib\Functions:: getCRMRecordMetadata($record);
		if ($recordMetaData) {
			$ownerId = $recordMetaData['smownerid'];
			$type = \App\Fields\Owner::getType($ownerId);
			$ownerArr[$type] = $ownerId;
		}
		\App\Log::trace('Exiting getRecordOwnerId method ...');
		return $ownerArr;
	}

	/** Function to update product quantity
	 * @param $product_id -- product id :: Type integer
	 * @param $upd_qty -- quantity :: Type integer
	 */
	public static function updateProductQty($product_id, $upd_qty)
	{

		\App\Log::trace("Entering updateProductQty(" . $product_id . "," . $upd_qty . ") method ...");
		$adb = \App\Database\PearDatabase::getInstance();
		$query = "update vtiger_products set qtyinstock=? where productid=?";
		$adb->pquery($query, array($upd_qty, $product_id));
		\App\Log::trace("Exiting updateProductQty method ...");
	}

	/**
	 * simple HTML to UTF-8 conversion:
	 */
	public static function html_to_utf8($data)
	{
		return preg_replace("/\\&\\#([0-9]{3,10})\\;/e", '_html_to_utf8("\\1")', $data);
	}

	public static function _html_to_utf8($data)
	{
		if ($data > 127) {
			$i = 5;
			while (($i--) > 0) {
				if ($data != ($a = $data % ($p = pow(64, $i)))) {
					$ret = chr(base_convert(str_pad(str_repeat(1, $i + 1), 8, "0"), 2, 10) + (($data - $a) / $p));
					for ($i; $i > 0; $i--)
						$ret .= chr(128 + ((($data % pow(64, $i)) - ($data % ($p = pow(64, $i - 1)))) / $p));
					break;
				}
			}
		} else
			$ret = "&#$data;";
		return $ret;
	}

	// Return Question mark
	public static function _questionify($v)
	{
		return "?";
	}

	/**
	 * Function to generate question marks for a given list of items
	 */
	public static function generateQuestionMarks($items_list)
	{
		// array_map will call the function specified in the first parameter for every element of the list in second parameter
		if (is_array($items_list)) {
			return implode(",", array_map([self::class, '_questionify'], $items_list));
		} else {
			return implode(",", array_map([self::class, '_questionify'], explode(",", $items_list)));
		}
	}

	/**
	 * Function to find the UI type of a field based on the uitype id
	 */
	public static function is_uitype($uitype, $reqtype)
	{
		$ui_type_arr = array(
			'_date_' => array(5, 6, 23, 70),
			'_picklist_' => array(15, 16, 52, 53, 54, 55, 59, 62, 63, 66, 68, 76, 77, 78, 80, 98, 101, 115, 357),
			'_users_list_' => array(52),
		);

		if ($ui_type_arr[$reqtype] != null) {
			if (in_array($uitype, $ui_type_arr[$reqtype])) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Function to escape quotes
	 * @param $value - String in which single quotes have to be replaced.
	 * @return Input string with single quotes escaped.
	 */
	public static function escape_single_quotes($value)
	{
		if (isset($value))
			$value = str_replace("'", "\'", $value);
		return $value;
	}

	/**
	 * Function to format the input value for SQL like clause.
	 * @param $str - Input string value to be formatted.
	 * @param $flag - By default set to 0 (Will look for cases %string%).
	 *                If set to 1 - Will look for cases %string.
	 *                If set to 2 - Will look for cases string%.
	 * @return String formatted as per the SQL like clause requirement
	 */
	public static function formatForSqlLike($str, $flag = 0, $is_field = false)
	{
		$adb = \App\Database\PearDatabase::getInstance();
		if (isset($str)) {
			if ($is_field === false) {
				$str = str_replace('%', '\%', $str);
				$str = str_replace('_', '\_', $str);
				if ($flag == 0) {
					// If value what to search is null then we should not add % which will fail
					if (empty($str))
						$str = '' . $str . '';
					else
						$str = '%' . $str . '%';
				} elseif ($flag == 1) {
					$str = '%' . $str;
				} elseif ($flag == 2) {
					$str = $str . '%';
				}
			} else {
				if ($flag == 0) {
					$str = 'concat("%",' . $str . ',"%")';
				} elseif ($flag == 1) {
					$str = 'concat("%",' . $str . ')';
				} elseif ($flag == 2) {
					$str = 'concat(' . $str . ',"%")';
				}
			}
		}
		return $adb->sql_escape_string($str);
	}

	/** Function to get on clause criteria for duplicate check queries */
	public static function get_on_clause($field_list, $uitype_arr, $module)
	{
		$field_array = explode(",", $field_list);
		$ret_str = '';
		$i = 1;
		foreach ($field_array as $fld) {
			$sub_arr = explode(".", $fld);
			$tbl_name = $sub_arr[0];
			$col_name = $sub_arr[1];
			$fld_name = $sub_arr[2];

			$ret_str .= " ifnull($tbl_name.$col_name,'null') = ifnull(temp.$col_name,'null')";

			if (count($field_array) != $i)
				$ret_str .= " and ";
			$i++;
		}
		return $ret_str;
	}

	/**
	 * this function searches for a given number in vtiger and returns the callerInfo in an array format
	 * currently the search is made across only leads, accounts and contacts modules
	 *
	 * @param $number - the number whose information you want
	 * @return array in format array(name=>callername, module=>module, id=>id);
	 */
	public static function getCallerInfo($number)
	{
		$adb = \App\Database\PearDatabase::getInstance();

		if (empty($number)) {
			return false;
		}
		$caller = "Unknown Number (Unknown)"; //declare caller as unknown in beginning

		$params = [];
		$name = array('Contacts', 'Accounts', 'Leads');
		foreach ($name as $module) {
			$focus = \App\CRMEntity::getInstance($module);
			$query = $focus->buildSearchQueryForFieldTypes(11, $number);
			if (empty($query))
				return;

			$result = $adb->pquery($query, []);
			if ($adb->num_rows($result) > 0) {
				$callerName = $adb->query_result($result, 0, "name");
				$callerID = $adb->query_result($result, 0, 'id');
				$data = array("name" => $callerName, "module" => $module, "id" => $callerID);
				return $data;
			}
		}
		return false;
	}

	/**
	 * this function returns the value of use_asterisk from the database for the current user
	 * @param string $id - the id of the current user
	 */
	public static function get_use_asterisk($id)
	{
		$adb = \App\Database\PearDatabase::getInstance();
		if (!\App\Utils\ModuleUtils::isModuleActive('PBXManager') || !\App\Security\Privilege::isPermitted('PBXManager', 'index')) {
			return false;
		}
		$sql = "select * from vtiger_asteriskextensions where userid = ?";
		$result = $adb->pquery($sql, array($id));
		if ($adb->num_rows($result) > 0) {
			$use_asterisk = $adb->query_result($result, 0, "use_asterisk");
			$asterisk_extension = $adb->query_result($result, 0, "asterisk_extension");
			if ($use_asterisk == 0 || empty($asterisk_extension)) {
				return 'false';
			} else {
				return 'true';
			}
		} else {
			return 'false';
		}
	}

	//functions for asterisk integration end

	/* Function to get the related tables data
	 * @param - $module - Primary module name
	 * @param - $secmodule - Secondary module name
	 * return Array $rel_array tables and fields to be compared are sent
	 * */
	public static function getRelationTables($module, $secmodule)
	{
		$adb = \App\Database\PearDatabase::getInstance();
		$primary_obj = \App\CRMEntity::getInstance($module);
		$secondary_obj = \App\CRMEntity::getInstance($secmodule);

		$ui10_query = $adb->pquery("SELECT vtiger_field.tabid AS tabid,vtiger_field.tablename AS tablename, vtiger_field.columnname AS columnname FROM vtiger_field INNER JOIN vtiger_fieldmodulerel ON vtiger_fieldmodulerel.fieldid = vtiger_field.fieldid WHERE (vtiger_fieldmodulerel.module=? && vtiger_fieldmodulerel.relmodule=?) || (vtiger_fieldmodulerel.module=? && vtiger_fieldmodulerel.relmodule=?)", array($module, $secmodule, $secmodule, $module));
		if ($adb->num_rows($ui10_query) > 0) {
			$ui10_tablename = $adb->query_result($ui10_query, 0, 'tablename');
			$ui10_columnname = $adb->query_result($ui10_query, 0, 'columnname');
			$ui10_tabid = $adb->query_result($ui10_query, 0, 'tabid');

			if ($primary_obj->table_name == $ui10_tablename) {
				$reltables = array($ui10_tablename => array("" . $primary_obj->table_index . "", "$ui10_columnname"));
			} else if ($secondary_obj->table_name == $ui10_tablename) {
				$reltables = array($ui10_tablename => array("$ui10_columnname", "" . $secondary_obj->table_index . ""), "" . $primary_obj->table_name . "" => "" . $primary_obj->table_index . "");
			} else {
				if (isset($secondary_obj->tab_name_index[$ui10_tablename])) {
					$rel_field = $secondary_obj->tab_name_index[$ui10_tablename];
					$reltables = array($ui10_tablename => array("$ui10_columnname", "$rel_field"), "" . $primary_obj->table_name . "" => "" . $primary_obj->table_index . "");
				} else {
					$rel_field = $primary_obj->tab_name_index[$ui10_tablename];
					$reltables = array($ui10_tablename => array("$rel_field", "$ui10_columnname"), "" . $primary_obj->table_name . "" => "" . $primary_obj->table_index . "");
				}
			}
		} else {
			if (method_exists($primary_obj, setRelationTables)) {
				$reltables = $primary_obj->setRelationTables($secmodule);
			} else {
				$reltables = '';
			}
		}
		if (is_array($reltables) && !empty($reltables)) {
			$rel_array = $reltables;
		} else {
			$rel_array = array("vtiger_crmentityrel" => array("crmid", "relcrmid"), "" . $primary_obj->table_name . "" => "" . $primary_obj->table_index . "");
		}
		return $rel_array;
	}

	/**
	 * This function returns no value but handles the delete functionality of each entity.
	 * Input Parameter are $module - module name, $return_module - return module name, $focus - module object, $record - entity id, $return_id - return entity id.
	 */
	public static function DeleteEntity($destinationModule, $sourceModule, $focus, $destinationRecordId, $sourceRecordId, $relatedName = false)
	{
		\App\Log::trace("Entering DeleteEntity method ($destinationModule, $sourceModule, $destinationRecordId, $sourceRecordId)");
		require_once(ROOT_DIRECTORY . '/src/events/include.php');
		if ($destinationModule != $sourceModule && !empty($sourceModule) && !empty($sourceRecordId)) {
			$eventHandler = new \App\EventHandler();
			$eventHandler->setModuleName($sourceModule);
			$eventHandler->setParams([
				'CRMEntity' => $focus,
				'sourceModule' => $sourceModule,
				'sourceRecordId' => $sourceRecordId,
				'destinationModule' => $destinationModule,
				'destinationRecordId' => $destinationRecordId,
			]);
			$eventHandler->trigger('EntityBeforeUnLink');

			$focus->unlinkRelationship($destinationRecordId, $sourceModule, $sourceRecordId, $relatedName);
			$focus->trackUnLinkedInfo($sourceModule, $sourceRecordId, $destinationModule, $destinationRecordId);

			$eventHandler->trigger('EntityAfterUnLink');
		} else {
			$currentUserPrivilegesModel = \App\Modules\Users\Models\Privileges::getCurrentUserPrivilegesModel();
			if (!$currentUserPrivilegesModel->isPermitted($destinationModule, 'Delete', $destinationRecordId)) {
				throw new \App\Exceptions\AppException(\App\Runtime\Vtiger_Language_Handler::translate('LBL_PERMISSION_DENIED'));
			}
			$focus->trash($destinationModule, $destinationRecordId);
		}
		\App\Log::trace('Exiting DeleteEntity method ...');
	}

	/**
	 * Function to related two records of different entity types
	 */
	public static function relateEntities($focus, $sourceModule, $sourceRecordId, $destinationModule, $destinationRecordIds, $relatedName = false)
	{
		\App\Log::trace("Entering relateEntities method ($sourceModule, $sourceRecordId, $destinationModule, $destinationRecordIds)");
		if (!is_array($destinationRecordIds))
			$destinationRecordIds = [$destinationRecordIds];

		$data = [
			'CRMEntity' => $focus,
			'sourceModule' => $sourceModule,
			'sourceRecordId' => $sourceRecordId,
			'destinationModule' => $destinationModule,
		];
		$eventHandler = new \App\EventHandler();
		$eventHandler->setModuleName($sourceModule);
		foreach ($destinationRecordIds as &$destinationRecordId) {
			$data['destinationRecordId'] = $destinationRecordId;
			$eventHandler->setParams($data);
			$eventHandler->trigger('EntityBeforeLink');
			$focus->save_related_module($sourceModule, $sourceRecordId, $destinationModule, $destinationRecordId, $relatedName);
			\App\CRMEntity::trackLinkedInfo($sourceRecordId);
			$eventHandler->trigger('EntityAfterLink');
		}
		\App\Log::trace("Exiting relateEntities method ...");
	}

	/**
	 * Function to check if a given record exists (not deleted)
	 * @param integer $recordId - record id
	 */
	public static function isRecordExists($recordId, $cache = true)
	{
		$recordMetaData = \vtlib\Functions:: getCRMRecordMetadata($recordId);
		return (isset($recordMetaData) && $recordMetaData['deleted'] == 0) ? true : false;
	}

	/** Function to set date values compatible to database (YY_MM_DD)
	 * @param $value -- value :: Type string
	 * @returns $insert_date -- insert_date :: Type string
	 */
	public static function getValidDBInsertDateValue($value)
	{

		\App\Log::trace("Entering getValidDBInsertDateValue(" . $value . ") method ...");
		$value = trim($value);
		$delim = array('/', '.');
		foreach ($delim as $delimiter) {
			$x = strpos($value, $delimiter);
			if ($x === false)
				continue;
			else {
				$value = str_replace($delimiter, '-', $value);
				break;
			}
		}
		list($y, $m, $d) = explode('-', $value);
		if (strlen($y) == 1)
			$y = '0' . $y;
		if (strlen($m) == 1)
			$m = '0' . $m;
		if (strlen($d) == 1)
			$d = '0' . $d;
		$value = implode('-', array($y, $m, $d));

		if (strlen($y) < 4) {
			$insert_date = \App\Fields\DateTimeField::convertToDBFormat($value);
		} else {
			$insert_date = $value;
		}

		if (preg_match("/^[0-9]{2,4}[-][0-1]{1,2}?[0-9]{1,2}[-][0-3]{1,2}?[0-9]{1,2}$/", $insert_date) == 0) {
			return '';
		}

		\App\Log::trace("Exiting getValidDBInsertDateValue method ...");
		return $insert_date;
	}

	public static function getValidDBInsertDateTimeValue($value)
	{
		$value = trim($value);
		$valueList = explode(' ', $value);
		if (count($valueList) == 2) {
			$dbDateValue = getValidDBInsertDateValue($valueList[0]);
			$dbTimeValue = $valueList[1];
			if (!empty($dbTimeValue) && strpos($dbTimeValue, ':') === false) {
				$dbTimeValue = $dbTimeValue . ':';
			}
			$timeValueLength = strlen($dbTimeValue);
			if (!empty($dbTimeValue) && strrpos($dbTimeValue, ':') == ($timeValueLength - 1)) {
				$dbTimeValue = $dbTimeValue . '00';
			}
			try {
				$dateTime = new \App\Fields\DateTimeField($dbDateValue . ' ' . $dbTimeValue);
				return $dateTime->getDBInsertDateTimeValue();
			} catch (\Exception $ex) {
				return '';
			}
		} elseif (count($valueList == 1)) {
			return getValidDBInsertDateValue($value);
		}
	}

	/** Function to return block name
	 * @param integer -- $blockid
	 * @return String - Block Name
	 */
	public static function getBlockName($blockid)
	{
		$adb = \App\Database\PearDatabase::getInstance();

		$blockname = \App\Utils\VTCacheUtils::lookupBlockLabelWithId($blockid);

		if (!empty($blockid) && $blockname === false) {
			$block_res = $adb->pquery('SELECT blocklabel FROM vtiger_blocks WHERE blockid = ?', array($blockid));
			if ($adb->num_rows($block_res)) {
				$blockname = $adb->query_result($block_res, 0, 'blocklabel');
			} else {
				$blockname = '';
			}
			\App\Utils\VTCacheUtils::updateBlockLabelWithId($blockname, $blockid);
		}
		return $blockname;
	}

	/**
	 * Function to get the approximate difference between two date time values as string
	 */
	public static function dateDiffAsString($d1, $d2, $moduleName = 'Vtiger')
	{
		$currentModule = $moduleName;

		$dateDiff = self::dateDiff($d1, $d2);

		$years = $dateDiff['years'];
		$months = $dateDiff['months'];
		$days = $dateDiff['days'];
		$hours = $dateDiff['hours'];
		$minutes = $dateDiff['minutes'];
		$seconds = $dateDiff['seconds'];

		if ($years > 0) {
			$diffString = "$years " . \App\Runtime\Vtiger_Language_Handler::translate('LBL_YEARS', $currentModule);
		} elseif ($months > 0) {
			$diffString = "$months " . \App\Runtime\Vtiger_Language_Handler::translate('LBL_MONTHS', $currentModule);
		} elseif ($days > 0) {
			$diffString = "$days " . \App\Runtime\Vtiger_Language_Handler::translate('LBL_DAYS', $currentModule);
		} elseif ($hours > 0) {
			$diffString = "$hours " . \App\Runtime\Vtiger_Language_Handler::translate('LBL_HOURS', $currentModule);
		} elseif ($minutes > 0) {
			$diffString = "$minutes " . \App\Runtime\Vtiger_Language_Handler::translate('LBL_MINUTES', $currentModule);
		} else {
			$diffString = "$seconds " . \App\Runtime\Vtiger_Language_Handler::translate('LBL_SECONDS', $currentModule);
		}
		return $diffString;
	}

	//Get the User selected NumberOfCurrencyDecimals
	public static function getCurrencyDecimalPlaces()
	{
		$currentUser = \App\User\CurrentUser::get();
		if ($currentUser && isset($currentUser->no_of_currency_decimals)) {
			return $currentUser->no_of_currency_decimals;
		}
		return 2;
	}

	public static function getInventoryModules()
	{
		$inventoryModules = [];
		return $inventoryModules;
	}

	/**
	 * Function to get the list of Contacts related to an activity
	 * @param integer $activityId
	 * @return array $contactsList - List of Contact ids, mapped to Contact Names
	 */
	public static function getActivityRelatedContacts($activityId)
	{
		$adb = \App\Database\PearDatabase::getInstance();

		$query = 'SELECT link FROM vtiger_activity WHERE activityid=?';
		$result = $adb->pquery($query, array($activityId));

		$noOfContacts = $adb->num_rows($result);
		$contactsList = [];
		for ($i = 0; $i < $noOfContacts; ++$i) {
			$contactId = $adb->query_result($result, $i, 'link');
			$displayValueArray = getEntityName('Contacts', $contactId);
			if (!empty($displayValueArray)) {
				foreach ($displayValueArray as $key => $field_value) {
					$contact_name = $field_value;
				}
			} else {
				$contact_name = '';
			}
			$contactsList[$contactId] = $contact_name;
		}
		return $contactsList;
	}

	/** Function to get the difference between 2 datetime strings or millisecond values */
	public static function dateDiff($d1, $d2)
	{
		$d1 = (is_string($d1) ? strtotime($d1) : $d1);
		$d2 = (is_string($d2) ? strtotime($d2) : $d2);

		$diffSecs = abs($d1 - $d2);
		$baseYear = min(date("Y", $d1), date("Y", $d2));
		$diff = mktime(0, 0, $diffSecs, 1, 1, $baseYear);
		return array(
			"years" => date("Y", $diff) - $baseYear,
			"months_total" => (date("Y", $diff) - $baseYear) * 12 + date("n", $diff) - 1,
			"months" => date("n", $diff) - 1,
			"days_total" => floor($diffSecs / (3600 * 24)),
			"days" => date("j", $diff) - 1,
			"hours_total" => floor($diffSecs / 3600),
			"hours" => date("G", $diff),
			"minutes_total" => floor($diffSecs / 60),
			"minutes" => (int) date("i", $diff),
			"seconds_total" => $diffSecs,
			"seconds" => (int) date("s", $diff)
		);
	}

	/** call back function to change the array values in to lower case */
	public static function lower_array(&$string)
	{
		$string = strtolower(trim($string));
	}
}
