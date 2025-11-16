<?php
/**
 * FreeCRM VTLib Utilities
 */

namespace App\Utils;

class VtlibUtils
{
	/**
	 * Check for image existence in themes or use the common one
	 * @param string $imagename Image filename
	 * @param string $themename Theme name
	 * @return string Image path
	 */
	public static function getImageUrl($imagename, $themename)
	{
		static $cacheVtigerImagepath = [];
		if (isset($cacheVtigerImagepath[$imagename])) {
			$imagepath = $cacheVtigerImagepath[$imagename];
		} else {
			$imagepath = false;
			// Check in theme specific folder
			if (file_exists("themes/$themename/images/$imagename")) {
				$imagepath = "themes/$themename/images/$imagename";
			} else if (file_exists("themes/images/$imagename")) {
				// Search in common image folder
				$imagepath = "themes/images/$imagename";
			} else {
				// Not found anywhere? Return whatever is sent
				$imagepath = $imagename;
			}
			$cacheVtigerImagepath[$imagename] = $imagepath;
		}
		return $imagepath;
	}

	/**
	 * Fetch module active information at one shot, but return all the information fetched
	 * @param bool $force Force refresh from database
	 * @return array Module tab information
	 */
	public static function prefetchModuleActiveInfo($force = true)
	{
		// Look up if cache has information
		$tabrows = VTCacheUtils::lookupAllTabsInfo();

		// Initialize from DB if cache information is not available or force flag is set
		if ($tabrows === false || $force) {
			$adb = \App\Database\PearDatabase::getInstance();
			$tabres = $adb->query("SELECT * FROM vtiger_tab");
			$tabrows = [];
			if ($tabres) {
				while ($tabresrow = $adb->fetch_array($tabres)) {
					$tabrows[] = $tabresrow;
				}
				// Update cache for further re-use
				VTCacheUtils::updateAllTabsInfo($tabrows);
			}
		}

		return $tabrows;
	}

	/**
	 * Recreate user privileges files
	 */
	public static function recreateUserPrivilegeFiles()
	{
		$adb = \App\Database\PearDatabase::getInstance();
		$userres = $adb->query('SELECT id FROM vtiger_users WHERE deleted = 0');
		if ($userres && $adb->num_rows($userres)) {
			while ($userrow = $adb->fetch_array($userres)) {
				\App\Modules\Users\Services\PrivilegeFileManager::createUserPrivilegesFile($userrow['id']);
			}
		}
	}

	/**
	 * Setup mandatory (required) module variable values in the module class
	 * @param string $module Module name
	 * @param object $focus Module object
	 */
	public static function setupModuleVars($module, $focus)
	{
		$checkfor = array('table_name', 'table_index', 'related_tables', 'popup_fields', 'IsCustomModule');
		foreach ($checkfor as $check) {
			if (!isset($focus->$check))
				$focus->$check = self::getModuleVarValue($module, $check);
		}
	}

	/**
	 * Get module variable value
	 * @param string $module Module name
	 * @param string $varname Variable name
	 * @return mixed Variable value
	 */
	private static function getModuleVarValue($module, $varname)
	{
		$mod_var_mapping = array(
			'Accounts' =>
			array(
				'IsCustomModule' => false,
				'table_name' => 'vtiger_account',
				'table_index' => 'accountid',
				'related_tables' => array(
					'vtiger_accountaddress' => array('accountaddressid', 'vtiger_account', 'accountid'),
					'vtiger_accountscf' => array('accountid', 'vtiger_account', 'accountid'),
				),
				'popup_fields' => array('accountname'),
			),
			'Contacts' =>
			array(
				'IsCustomModule' => false,
				'table_name' => 'vtiger_contactdetails',
				'table_index' => 'contactid',
				'related_tables' => array(
					'vtiger_account' => array('parentid'),
					'vtiger_contactaddress' => array('contactaddressid', 'vtiger_contactdetails', 'contactid'),
					'vtiger_contactsubdetails' => array('contactsubscriptionid', 'vtiger_contactdetails', 'contactid'),
					'vtiger_customerdetails' => array('customerid', 'vtiger_contactdetails', 'contactid'),
					'vtiger_contactscf' => array('contactid', 'vtiger_contactdetails', 'contactid')
				),
				'popup_fields' => array('lastname'),
			),
			'Leads' =>
			array(
				'IsCustomModule' => false,
				'table_name' => 'vtiger_leaddetails',
				'table_index' => 'leadid',
				'related_tables' => array(
					'vtiger_leadsubdetails' => array('leadsubscriptionid', 'vtiger_leaddetails', 'leadid'),
					'vtiger_leadaddress' => array('leadaddressid', 'vtiger_leaddetails', 'leadid'),
					'vtiger_leadscf' => array('leadid', 'vtiger_leaddetails', 'leadid'),
				),
				'popup_fields' => array('company'),
			),
			'Campaigns' =>
			array(
				'IsCustomModule' => false,
				'table_name' => 'vtiger_campaign',
				'table_index' => 'campaignid',
				'popup_fields' => array('campaignname'),
			),
			'HelpDesk' =>
			array(
				'IsCustomModule' => false,
				'table_name' => 'vtiger_troubletickets',
				'table_index' => 'ticketid',
				'related_tables' => array('vtiger_ticketcf' => array('ticketid')),
				'popup_fields' => array('ticket_title')
			),
			'Faq' =>
			array(
				'IsCustomModule' => false,
				'table_name' => 'vtiger_faq',
				'table_index' => 'id',
			),
			'Documents' =>
			array(
				'IsCustomModule' => false,
				'table_name' => 'vtiger_notes',
				'table_index' => 'notesid',
			),
			'Products' =>
			array(
				'IsCustomModule' => false,
				'table_name' => 'vtiger_products',
				'table_index' => 'productid',
				'popup_fields' => array('productname'),
			),
			'PriceBooks' =>
			array(
				'IsCustomModule' => false,
				'table_name' => 'vtiger_pricebook',
				'table_index' => 'pricebookid',
			),
			'Vendors' =>
			array(
				'IsCustomModule' => false,
				'table_name' => 'vtiger_vendor',
				'table_index' => 'vendorid',
				'popup_fields' => array('vendorname'),
			),
			'Project' =>
			array(
				'IsCustomModule' => false,
				'table_name' => 'vtiger_project',
				'table_index' => 'projectid',
				'related_tables' => array(
					'vtiger_projectcf' => array('projectid', 'vtiger_project', 'projectid')
				),
			),
			'ProjectMilestone' =>
			array(
				'IsCustomModule' => false,
				'table_name' => 'vtiger_projectmilestone',
				'table_index' => 'projectmilestoneid',
				'related_tables' => array(
					'vtiger_projectmilestonecf' => array('projectmilestoneid', 'vtiger_projectmilestone', 'projectmilestoneid')
				),
			),
			'ProjectTask' =>
			array(
				'IsCustomModule' => false,
				'table_name' => 'vtiger_projecttask',
				'table_index' => 'projecttaskid',
				'related_tables' => array(
					'vtiger_projecttaskcf' => array('projecttaskid', 'vtiger_projecttask', 'projecttaskid')
				),
			),
			'Services' =>
			array(
				'IsCustomModule' => false,
				'table_name' => 'vtiger_service',
				'table_index' => 'serviceid',
				'related_tables' => array(
					'vtiger_servicecf' => array('serviceid')
				),
			),
			'ServiceContracts' =>
			array(
				'IsCustomModule' => false,
				'table_name' => 'vtiger_servicecontracts',
				'table_index' => 'servicecontractsid',
				'related_tables' => array(
					'vtiger_servicecontractscf' => array('servicecontractsid')
				),
			),
			'Assets' =>
			array(
				'IsCustomModule' => false,
				'table_name' => 'vtiger_assets',
				'table_index' => 'assetsid',
				'related_tables' => array(
					'vtiger_assetscf' => array('assetsid')
				),
			)
		);
		return $mod_var_mapping[$module][$varname] ?? null;
	}

	/**
	 * Get picklist values that is accessible by all roles
	 * @param string $fieldColumnname Field column name
	 * @return array Picklist values accessible to all roles
	 */
	public static function getPicklistValuesAccessibleToAll($fieldColumnname)
	{
		\App\Log\Log::trace('Entering ' . __METHOD__ . '(' . print_r($fieldColumnname, true) . ') method ...');
		$adb = \App\Database\PearDatabase::getInstance();

		$columnname = $adb->quote($fieldColumnname, false);
		$tablename = 'vtiger_' . $fieldColumnname;
		// Gather all the roles (except H1 which is organization role)
		$roleres = $adb->query("SELECT roleid FROM vtiger_role WHERE roleid != 'H1'");
		$roleresCount = $adb->num_rows($roleres);
		$allroles = [];
		if ($roleresCount) {
			for ($index = 0; $index < $roleresCount; ++$index)
				$allroles[] = $adb->query_result($roleres, $index, 'roleid');
		}
		sort($allroles);

		// Get all the picklist values associated to roles (except H1 - organization role).
		$picklistres = $adb->query(
			"SELECT $columnname as pickvalue, roleid FROM $tablename
		INNER JOIN vtiger_role2picklist ON $tablename.picklist_valueid=vtiger_role2picklist.picklistvalueid
		WHERE roleid != 'H1'");

		$picklistresCount = $adb->num_rows($picklistres);

		$picklistval_roles = [];
		if ($picklistresCount) {
			while ($row = $adb->getRow($picklistres)) {
				$picklistval_roles[$row['pickvalue']][] = $row['roleid'];
			}
		}
		// Collect picklist value which is associated to all the roles.
		$allrolevalues = [];
		foreach ($picklistval_roles as $picklistval => $pickvalroles) {
			sort($pickvalroles);
			$diff = array_diff($pickvalroles, $allroles);
			if (empty($diff))
				$allrolevalues[] = $picklistval;
		}

		\App\Log\Log::trace('Exiting ' . __METHOD__ . ' method ...');
		return $allrolevalues;
	}

	/**
	 * Get all picklist values for a non-standard picklist type
	 * @param string $columnname Column name
	 * @return array Picklist values
	 */
	public static function getPicklistValues($columnname)
	{
		$adb = \App\Database\PearDatabase::getInstance();

		$tablename = "vtiger_$columnname";
		$tablename = $adb->quote($tablename, false);

		$picklistres = $adb->query("SELECT $columnname as pickvalue FROM $tablename");
		$picklistresCount = $adb->num_rows($picklistres);

		$picklistvalues = [];
		if ($picklistresCount) {
			for ($index = 0; $index < $picklistresCount; ++$index) {
				$picklistvalues[] = $adb->query_result($picklistres, $index, 'pickvalue');
			}
		}
		return $picklistvalues;
	}

	/**
	 * Check if given path is writeable
	 * @param string $path Path to check
	 * @return bool True if writeable
	 */
	public static function isWriteable($path)
	{
		$path = ROOT_DIRECTORY . DIRECTORY_SEPARATOR . $path;
		if (is_dir($path)) {
			return self::isDirWriteable($path);
		} else {
			return is_writable($path);
		}
	}

	/**
	 * Check if given directory is writeable
	 * NOTE: The check is made by trying to create a random file in the directory
	 * @param string $dirpath Directory path
	 * @return bool True if writeable
	 */
	public static function isDirWriteable($dirpath)
	{
		if (is_dir($dirpath)) {
			do {
				$tmpfile = 'vtiger' . time() . '-' . rand(1, 1000) . '.tmp';
				// Continue the loop unless we find a name that does not exists already.
				$usefilename = "$dirpath/$tmpfile";
				if (!file_exists($usefilename))
					break;
			} while (true);
			$fh = @fopen($usefilename, 'a');
			if ($fh) {
				fclose($fh);
				unlink($usefilename);
				return true;
			}
		}
		return false;
	}
}

