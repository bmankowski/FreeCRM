<?php

namespace App\Modules\Users;

/* * *******************************************************************************
 * The contents of this file are subject to the SugarCRM Public License Version 1.1.2
 * ("License"); You may not use this file except in compliance with the
 * License. You may obtain a copy of the License at http://www.sugarcrm.com/SPL
 * Software distributed under the License is distributed on an  "AS IS"  basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License for
 * the specific language governing rights and limitations under the License.
 * The Original Code is:  SugarCRM Open Source
 * The Initial Developer of the Original Code is SugarCRM, Inc.
 * Portions created by SugarCRM are Copyright (C) SugarCRM, Inc.;
 * All Rights Reserved.
 * Contributor(s): YetiForce.com.
 * ****************************************************************************** */
/* * *******************************************
 * With modifications by
 * Daniel Jabbour
 * iWebPress Incorporated, www.iwebpress.com
 * djabbour - a t - iwebpress - d o t - com
 * ****************************************** */
/* * *******************************************************************************
 * $Header: /advent/projects/wesat/vtiger_crm/sugarcrm/modules/Users/Users.php,v 1.10 2005/04/19 14:40:48 ray Exp $
 * Portions created by SugarCRM are Copyright (C) SugarCRM, Inc.
 * All Rights Reserved.
 * Contributor(s): YetiForce.com.
 * ****************************************************************************** */

// User is used to store customer information.
/** Main class for the user module
 *
 */
class Users extends \App\CRMEntity
{

	// Stored fields
	public $id;
	public $authenticated = false;
	public $error_string;
	public $is_admin;
	public $deleted;
	
	// Dynamic properties for user info
	public $currency_name;
	public $currency_code;
	public $currency_symbol;
	public $conv_rate;
	
	// User database fields - declared to avoid PHP 8.2+ deprecation warnings
	public $user_name;
	public $user_password;
	public $confirm_password;
	public $first_name;
	public $last_name;
	public $roleid;
	public $email1;
	public $status;
	public $activity_view;
	public $lead_view;
	public $hour_format;
	public $start_hour;
	public $reports_to_id;
	public $date_format;
	public $description;
	public $accesskey;
	public $time_zone;
	public $currency_id;
	public $currency_grouping_pattern;
	public $currency_decimal_separator;
	public $currency_grouping_separator;
	public $currency_symbol_placement;
	public $imagename;
	public $internal_mailer;
	public $theme;
	public $language;
	public $reminder_interval;
	public $phone_crm_extension;
	public $no_of_currency_decimals;
	public $truncate_trailing_zeros;
	public $dayoftheweek;
	public $callduration;
	public $othereventduration;
	public $calendarsharedtype;
	public $default_record_view;
	public $leftpanelhide;
	public $rowheight;
	public $defaulteventstatus;
	public $defaultactivitytype;
	public $is_owner;
	public $end_hour;
	public $emailoptout;
	public $available;
	public $auto_assign;
	
	// Additional user database fields
	public $cal_color;
	public $tz;
	public $holidays;
	public $namedays;
	public $workdays;
	public $weekstart;
	public $reminder_next_time;
	public $crypt_type;
	public $records_limit;
	public $tab_name = ['vtiger_users', 'vtiger_user2role'];
	public $tab_name_index = ['vtiger_users' => 'id', 'vtiger_user2role' => 'userid'];
	public $table_name = "vtiger_users";
	public $table_index = 'id';
	// This is the list of fields that are in the lists.
	public $list_link_field = 'last_name';
	public $list_mode;
	public $popup_type;
	public $search_fields = Array(
		'Name' => Array('vtiger_users' => 'last_name'),
		'Email' => Array('vtiger_users' => 'email1')
	);
	public $search_fields_name = Array(
		'Name' => 'last_name',
		'Email' => 'email1'
	);
	public $module_name = "Users";
	public $object_name = "User";
	public $user_preferences;
	public $homeorder_array = array('HDB', 'ALVT', 'CVLVT', 'HLT', 'GRT', 'MNL', 'LTFAQ', 'UA', 'PA');
	public $encodeFields = Array("first_name", "last_name", "description");
	// This is used to retrieve related fields from form posts.
	public $additional_column_fields = Array('reports_to_name');
	// This is the list of vtiger_fields that are in the lists.
	public $list_fields = Array(
		'First Name' => Array('vtiger_users' => 'first_name'),
		'Last Name' => Array('vtiger_users' => 'last_name'),
		'Role Name' => Array('vtiger_user2role' => 'roleid'),
		'User Name' => Array('vtiger_users' => 'user_name'),
		'Status' => Array('vtiger_users' => 'status'),
		'Admin' => Array('vtiger_users' => 'is_admin')
	);
	public $list_fields_name = Array(
		'First Name' => 'first_name',
		'Last Name' => 'last_name',
		'Role Name' => 'roleid',
		'User Name' => 'user_name',
		'Status' => 'status',
		'Admin' => 'is_admin'
	);
	//Default Fields for Email Templates -- Pavani
	public $emailTemplate_defaultFields = array('first_name', 'last_name', 'title', 'department', 'phone_home', 'phone_mobile', 'signature', 'email1');
	public $popup_fields = array('last_name');
	// This is the list of fields that are in the lists.
	public $default_order_by = '';
	public $default_sort_order = 'ASC';
	public $record_id;
	public $new_schema = true;
	//Default Widgests
	public $default_widgets = array('CVLVT', 'UA');

	/** constructor function for the main user class
	  instantiates the Logger class and PearDatabase Class
	 *
	 */
	public function __construct()
	{
		$this->db = \App\Database\PearDatabase::getInstance();
		$this->column_fields = \App\Utils\Utils::getColumnFields('Users');
		$this->column_fields['currency_name'] = '';
		$this->column_fields['currency_code'] = '';
		$this->column_fields['currency_symbol'] = '';
		$this->column_fields['conv_rate'] = '';
	}

	// Mike Crowe Mod --------------------------------------------------------Default ordering for us
	/**
	 * Function to get sort order
	 * return string  $sorder    - sortorder string either 'ASC' or 'DESC'
	 */
	public function getSortOrder()
	{

		\App\Log::trace("Entering getSortOrder() method ...");
		if (\App\Http\AppRequest::has('sorder'))
			$sorder = $this->db->sql_escape_string(\App\Http\AppRequest::get('sorder'));
		else
			$sorder = (($_SESSION['USERS_SORT_ORDER'] != '') ? ($_SESSION['USERS_SORT_ORDER']) : ($this->default_sort_order));
		\App\Log::trace("Exiting getSortOrder method ...");
		return $sorder;
	}

	/**
	 * Function to get order by
	 * return string  $order_by    - fieldname(eg: 'subject')
	 */
	public function getOrderBy()
	{

		\App\Log::trace("Entering getOrderBy() method ...");

		$use_default_order_by = '';
		if (\App\AppConfig::performance('LISTVIEW_DEFAULT_SORTING', true)) {
			$use_default_order_by = $this->default_order_by;
		}

		if (\App\Http\AppRequest::has('order_by'))
			$order_by = $this->db->sql_escape_string(\App\Http\AppRequest::get('order_by'));
		else
			$order_by = (($_SESSION['USERS_ORDER_BY'] != '') ? ($_SESSION['USERS_ORDER_BY']) : ($use_default_order_by));
		\App\Log::trace("Exiting getOrderBy method ...");
		return $order_by;
	}
	// Mike Crowe Mod --------------------------------------------------------

	/** Function to set the user preferences in the session
	 * @param $name -- name:: Type varchar
	 * @param $value -- value:: Type varchar
	 *
	 */

	/**
	 * @return string encrypted password for storage in DB and comparison against DB password.
	 * @param string $user_name - Must be non null and at least 2 characters
	 * @param string $user_password - Must be non null and at least 1 character.
	 * @desc Take an unencrypted username and password and return the encrypted password
	 * Portions created by SugarCRM are Copyright (C) SugarCRM, Inc..
	 * All Rights Reserved..
	 * Contributor(s): ______________________________________..
	 */

	/** Function for validation check
	 *
	 */
	public function validation_check($validate, $md5, $alt = '')
	{
		return \App\Modules\Users\Models\Record::validateFile($validate, $md5, $alt);
	}

	/** Function for authorization check
	 *
	 */
	public function authorization_check($validate, $authkey, $i)
	{
		return \App\Modules\Users\Models\Record::checkAuthorization($validate, $authkey, $i);
	}

	/**
	 * Checks the config.php AUTHCFG value for login type and forks off to the proper module
	 * @param string $userPassword - The password of the user to authenticate
	 * @return bool true if the user is authenticated, false otherwise
	 */
	public function doLogin($userPassword)
	{
		// Delegate to Record model for authentication logic
		$userModel = \App\Modules\Users\Models\Record::getInstanceByName($this->column_fields['user_name']);
		if ($userModel && $userModel->doLogin($userPassword)) {
			// Sync the entity with the Record model state
			$this->column_fields['id'] = $userModel->getId();
			$this->authenticated = $userModel->isAuthenticated();
			return true;
		}
		$this->authenticated = false;
		return false;
	}



	/**
	 * @param string $user name - Must be non null and at least 1 character.
	 * @param string $userPassword - Must be non null and at least 1 character.
	 * @param string $newPassword - Must be non null and at least 1 character.
	 * @return boolean - If passwords pass verification and query succeeds, return true, else return false.
	 * @desc Verify that the current password is correct and write the new password to the DB.
	 * Portions created by SugarCRM are Copyright (C) SugarCRM, Inc..
	 * All Rights Reserved..
	 * Contributor(s): Contributor(s): YetiForce.com
	 */


	/** Function to get the current user information from the user_privileges file
	 * @param $userid -- user id:: Type integer
	 * @returns user info in $this->column_fields array:: Type array
	 *
	 */
	public function retrieveCurrentUserInfoFromFile($userid)
	{
		// Create a new Record instance and load user info
		$record = new \App\Modules\Users\Models\Record();
		$record->loadUserInfoFromFile($userid);
		
		// Sync data to current object for backward compatibility
		$recordData = $record->getData();
		foreach ($recordData as $field => $value) {
			$this->$field = $value;
			$this->column_fields[$field] = $value;
		}
		$this->id = $userid;
		return $this;
	}

	/**
	 * Function to insert values into the attachment table
	 * @param $id -- entity id:: Type integer
	 * @param $module -- module:: Type varchar
	 */
	public function insertIntoAttachment($id, $module)
	{

		\App\Log::trace("Entering into insertIntoAttachment($id,$module) method.");

		foreach ($_FILES as $fileindex => $files) {
			if ($files['name'] != '' && $files['size'] > 0) {
				$files['original_name'] = \App\Http\AppRequest::get($fileindex . '_hidden');
				$this->uploadAndSaveFile($id, $module, $files);
			}
		}

		\App\Log::trace("Exiting from insertIntoAttachment($id,$module) method.");
	}

	/** Function to retreive the user info of the specifed user id The user info will be available in $this->column_fields array
	 * @param $record -- record id:: Type integer
	 * @param $module -- module:: Type varchar
	 */
	public function retrieve_entity_info($record, $module)
	{
		$adb = \App\Database\PearDatabase::getInstance();

		\App\Log::trace("Entering into retrieve_entity_info($record, $module) method.");

		if ($record == '') {
			\App\Log::error('record is empty. returning null');
			return null;
		}
		$result = [];
		foreach ($this->tab_name_index as $tableName => $index) {
			$result[$tableName] = (new \App\Db\Query())
					->from($tableName)
					->where([$index => $record])->one();
		}
		$fields = \vtlib\Functions::getModuleFieldInfos($module);
		foreach ($fields as $fieldName => &$fieldRow) {
			if (isset($result[$fieldRow['tablename']][$fieldRow['columnname']])) {
				$value = $result[$fieldRow['tablename']][$fieldRow['columnname']];
				$this->column_fields[$fieldName] = $value;
				$this->$fieldName = $value;
			}
		}
		$this->column_fields['record_id'] = $record;
		$this->column_fields['record_module'] = $module;

		if (!empty($this->column_fields['currency_id'])) {
			$currency = (new \App\Db\Query())->from('vtiger_currency_info')->where(['id' => $this->column_fields['currency_id'], 'deleted' => 0])->one();
		}
		if (empty($currency)) {
			$currency = (new \App\Db\Query())->from('vtiger_currency_info')->where(['id' => 1])->one();
		}
		$currencyArray = ['$' => '&#36;', '&euro;' => '&#8364;', '&pound;' => '&#163;', '&yen;' => '&#165;'];
		if (isset($currencyArray[$currency['currency_symbol']])) {
			$currencySymbol = $currencyArray[$currency['currency_symbol']];
		} else {
			$currencySymbol = $currency['currency_symbol'];
		}
		$this->column_fields['currency_name'] = $this->currency_name = $currency['currency_name'];
		$this->column_fields['currency_code'] = $this->currency_code = $currency['currency_code'];
		$this->column_fields['currency_symbol'] = $this->currency_symbol = $currencySymbol;
		$this->column_fields['conv_rate'] = $this->conv_rate = $currency['conversion_rate'];
		if ($this->column_fields['no_of_currency_decimals'] === '') {
			$this->column_fields['no_of_currency_decimals'] = $this->no_of_currency_decimals = \App\Utils\Utils::getCurrencyDecimalPlaces();
		}
		if ($this->column_fields['currency_grouping_pattern'] == '' && $this->column_fields['currency_symbol_placement'] == '') {
			$this->column_fields['currency_grouping_pattern'] = $this->currency_grouping_pattern = '123,456,789';
			$this->column_fields['currency_decimal_separator'] = $this->currency_decimal_separator = '.';
			$this->column_fields['currency_grouping_separator'] = $this->currency_grouping_separator = ' ';
			$this->column_fields['currency_symbol_placement'] = $this->currency_symbol_placement = '1.0$';
		}
		$this->id = $record;
		\App\Log::trace('Exit from retrieve_entity_info() method.');
		return $this;
	}

	/** Function to upload the file to the server and add the file details in the attachments table
	 * @param string $id
	 * @param string $module
	 * @param array $fileDetails
	 * @return boolean
	 */
	public function uploadAndSaveFile($id, $module, $fileDetails)
	{
		\App\Log::trace("Entering into uploadAndSaveFile($id,$module,$fileDetails) method.");
		$currentUserId = \App\User::getCurrentUserId();
		$dateVar = date('Y-m-d H:i:s');
		$db = \App\Db::getInstance();
		//to get the owner id
		$ownerid = $this->column_fields['assigned_user_id'];
		if (!isset($ownerid) || $ownerid == '')
			$ownerid = $currentUserId;
		$fileInstance = \App\Fields\File::loadFromRequest($fileDetails);
		if (!$fileInstance->validate('image')) {
			\App\Log::trace('Skip the save attachment process.');
			return false;
		}
		$binFile = $fileInstance->getSanitizeName();
		$fileName = ltrim(basename(" " . $binFile)); //allowed filename like UTF-8 characters
		$fileType = $fileDetails['type'];
		$fileTmpName = $fileDetails['tmp_name'];
		$uploadFilePath = \vtlib\Functions::initStorageFileDirectory($module);
		$db->createCommand()->insert('vtiger_crmentity', [
			'smcreatorid' => $currentUserId,
			'smownerid' => $ownerid,
			'setype' => $module . ' Attachment',
			'description' => $this->column_fields['description'],
			'createdtime' => $dateVar,
			'modifiedtime' => $dateVar
		])->execute();
		$currentId = $db->getLastInsertID('vtiger_crmentity_crmid_seq');
		//upload the file in server
		$success = move_uploaded_file($fileTmpName, $uploadFilePath . $currentId . "_" . $binFile);
		if ($success) {
			$db->createCommand()->insert('vtiger_attachments', [
				'attachmentsid' => $currentId,
				'name' => $fileName,
				'description' => $this->column_fields['description'],
				'type' => $fileType,
				'path' => $uploadFilePath,
			])->execute();
			if ($id != '') {
				$db->createCommand()->delete('vtiger_salesmanattachmentsrel', ['smid' => $id])->execute();
			}
			$db->createCommand()->insert('vtiger_salesmanattachmentsrel', ['smid' => $id, 'attachmentsid' => $currentId])->execute();
			//we should update the imagename in the users table
			$db->createCommand()->update('vtiger_users', ['imagename' => $id], ['id' => $currentId])->execute();
			\App\Log::trace("Exiting from uploadAndSaveFile($id,$module,$fileDetails) method.");
			return true;
		}
		\App\Log::trace("Exiting from uploadAndSaveFile($id,$module,$fileDetails) method.");
		return false;
	}

	/**
	 * gives the order in which the modules have to be displayed in the home page for the specified user id
	 * @param $id -- user id:: Type integer
	 * @returns the customized home page order in $return_array
	 */
	public function getHomeStuffOrder($id)
	{
		$adb = \App\Database\PearDatabase::getInstance();
		if (!is_array($this->homeorder_array)) {
			$this->homeorder_array = array('UA', 'PA', 'ALVT', 'HDB', 'CVLVT', 'HLT',
				'GRT', 'MNL', 'LTFAQ');
		}
		$return_array = [];
		$homeorder = [];
		if ($id != '') {
			$qry = " select distinct(vtiger_homedefault.hometype) from vtiger_homedefault inner join vtiger_homestuff  on vtiger_homestuff.stuffid=vtiger_homedefault.stuffid where vtiger_homestuff.visible=0 and vtiger_homestuff.userid=?";
			$res = $adb->pquery($qry, array($id));
			$rows_res = $adb->num_rows($res);
			for ($q = 0; $q < $rows_res; $q++) {
				$homeorder[] = $adb->query_result($res, $q, "hometype");
			}
			$countHomeorderArray = count($this->homeorder_array);
			for ($i = 0; $i < $countHomeorderArray; $i++) {
				if (in_array($this->homeorder_array[$i], $homeorder)) {
					$return_array[$this->homeorder_array[$i]] = $this->homeorder_array[$i];
				} else {
					$return_array[$this->homeorder_array[$i]] = '';
				}
			}
		} else {
			$countHomeorderArray = count($this->homeorder_array);
			for ($i = 0; $i < $countHomeorderArray; $i++) {
				if (in_array($this->homeorder_array[$i], $this->default_widgets)) {
					$return_array[$this->homeorder_array[$i]] = $this->homeorder_array[$i];
				} else {
					$return_array[$this->homeorder_array[$i]] = '';
				}
			}
		}
		return $return_array;
	}

	public function getDefaultHomeModuleVisibility($home_string, $inVal)
	{
		$homeModComptVisibility = 1;
		if ($inVal == 'postinstall') {
			if (\App\Http\AppRequest::get($home_string) != '') {
				$homeModComptVisibility = 0;
			} else if (in_array($home_string, $this->default_widgets)) {
				$homeModComptVisibility = 0;
			}
		}
		return $homeModComptVisibility;
	}

	public function insertUserdetails($inVal)
	{
		$adb = \App\Database\PearDatabase::getInstance();
		$uid = $this->id;
		$s1 = $adb->getUniqueID("vtiger_homestuff");
		$visibility = $this->getDefaultHomeModuleVisibility('ALVT', $inVal);
		$sql = "insert into vtiger_homestuff values(?,?,?,?,?,?)";
		$res = $adb->pquery($sql, array($s1, 1, 'Default', $uid, $visibility, 'Top Accounts'));

		$s2 = $adb->getUniqueID("vtiger_homestuff");
		$visibility = $this->getDefaultHomeModuleVisibility('HDB', $inVal);
		$sql = "insert into vtiger_homestuff values(?,?,?,?,?,?)";
		$res = $adb->pquery($sql, array($s2, 2, 'Default', $uid, $visibility, 'Home Page Dashboard'));

		$s5 = $adb->getUniqueID("vtiger_homestuff");
		$visibility = $this->getDefaultHomeModuleVisibility('CVLVT', $inVal);
		$sql = "insert into vtiger_homestuff values(?,?,?,?,?,?)";
		$res = $adb->pquery($sql, array($s5, 5, 'Default', $uid, $visibility, 'Key Metrics'));

		$s6 = $adb->getUniqueID("vtiger_homestuff");
		$visibility = $this->getDefaultHomeModuleVisibility('HLT', $inVal);
		$sql = "insert into vtiger_homestuff values(?,?,?,?,?,?)";
		$res = $adb->pquery($sql, array($s6, 6, 'Default', $uid, $visibility, 'Top Trouble Tickets'));

		$s7 = $adb->getUniqueID("vtiger_homestuff");
		$visibility = $this->getDefaultHomeModuleVisibility('UA', $inVal);
		$sql = "insert into vtiger_homestuff values(?,?,?,?,?,?)";
		$res = $adb->pquery($sql, array($s7, 7, 'Default', $uid, $visibility, 'Upcoming Activities'));

		$s8 = $adb->getUniqueID("vtiger_homestuff");
		$visibility = $this->getDefaultHomeModuleVisibility('GRT', $inVal);
		$sql = "insert into vtiger_homestuff values(?,?,?,?,?,?)";
		$res = $adb->pquery($sql, array($s8, 8, 'Default', $uid, $visibility, 'My Group Allocation'));

		$s11 = $adb->getUniqueID("vtiger_homestuff");
		$visibility = $this->getDefaultHomeModuleVisibility('MNL', $inVal);
		$sql = "insert into vtiger_homestuff values(?,?,?,?,?,?)";
		$res = $adb->pquery($sql, array($s11, 11, 'Default', $uid, $visibility, 'My New Leads'));

		$s13 = $adb->getUniqueID("vtiger_homestuff");
		$visibility = $this->getDefaultHomeModuleVisibility('PA', $inVal);
		$sql = "insert into vtiger_homestuff values(?,?,?,?,?,?)";
		$res = $adb->pquery($sql, array($s13, 13, 'Default', $uid, $visibility, 'Pending Activities'));
		;

		$s14 = $adb->getUniqueID("vtiger_homestuff");
		$visibility = $this->getDefaultHomeModuleVisibility('LTFAQ', $inVal);
		$sql = "insert into vtiger_homestuff values(?,?,?,?,?,?)";
		$res = $adb->pquery($sql, array($s14, 14, 'Default', $uid, $visibility, 'My Recent FAQs'));

		$sql = "insert into vtiger_homedefault values(" . $s1 . ",'ALVT',5,'Accounts')";
		$adb->pquery($sql, []);

		$sql = "insert into vtiger_homedefault values(" . $s2 . ",'HDB',5,'Dashboard')";
		$adb->pquery($sql, []);

		$sql = "insert into vtiger_homedefault values(" . $s5 . ",'CVLVT',5,'NULL')";
		$adb->pquery($sql, []);

		$sql = "insert into vtiger_homedefault values(" . $s6 . ",'HLT',5,'HelpDesk')";
		$adb->pquery($sql, []);

		$sql = "insert into vtiger_homedefault values(" . $s7 . ",'UA',5,'Calendar')";
		$adb->pquery($sql, []);

		$sql = "insert into vtiger_homedefault values(" . $s8 . ",'GRT',5,'NULL')";
		$adb->pquery($sql, []);

		$sql = "insert into vtiger_homedefault values(" . $s11 . ",'MNL',5,'Leads')";
		$adb->pquery($sql, []);

		$sql = "insert into vtiger_homedefault values(" . $s13 . ",'PA',5,'Calendar')";
		$adb->pquery($sql, []);

		$sql = "insert into vtiger_homedefault values(" . $s14 . ",'LTFAQ',5,'Faq')";
		$adb->pquery($sql, []);
	}

	/** function to save the order in which the modules have to be displayed in the home page for the specified user id
	 * @param $id -- user id:: Type integer
	 */
	public function saveHomeStuffOrder($id)
	{
		$adb = \App\Database\PearDatabase::getInstance();

		\App\Log::trace("Entering in function saveHomeOrder($id)");

		if ($this->mode == 'edit') {
			$countHomeorderArray = count($this->homeorder_array);
			for ($i = 0; $i < $countHomeorderArray; $i++) {
				if (\App\Http\AppRequest::get($this->homeorder_array[$i]) != '') {
					$save_array[] = $this->homeorder_array[$i];
					$qry = " update vtiger_homestuff,vtiger_homedefault set vtiger_homestuff.visible=0 where vtiger_homestuff.stuffid=vtiger_homedefault.stuffid and vtiger_homestuff.userid = ? and vtiger_homedefault.hometype= ?"; //To show the default Homestuff on the the Home Page
					$result = $adb->pquery($qry, [$id, $this->homeorder_array[$i]]);
				} else {

					$qry = "update vtiger_homestuff,vtiger_homedefault set vtiger_homestuff.visible=1 where vtiger_homestuff.stuffid=vtiger_homedefault.stuffid and vtiger_homestuff.userid= ? and vtiger_homedefault.hometype=?"; //To hide the default Homestuff on the the Home Page
					$result = $adb->pquery($qry, [$id, $this->homeorder_array[$i]]);
				}
			}
			if ($save_array != "")
				$homeorder = implode(',', $save_array);
		}
		else {
			$this->insertUserdetails('postinstall');
		}
		\App\Log::trace("Exiting from function saveHomeOrder($id)");
	}

	public function filterInactiveFields($module)
	{
		
	}

	public function deleteImage()
	{
		$sql1 = 'SELECT attachmentsid FROM vtiger_salesmanattachmentsrel WHERE smid = ?';
		$res1 = $this->db->pquery($sql1, array($this->id));
		if ($this->db->num_rows($res1) > 0) {
			$attachmentId = $this->db->query_result($res1, 0, 'attachmentsid');

			$sql2 = "DELETE FROM vtiger_crmentity WHERE crmid=? && setype='Users Attachments'";
			$this->db->pquery($sql2, array($attachmentId));

			$sql3 = 'DELETE FROM vtiger_salesmanattachmentsrel WHERE smid=? && attachmentsid=?';
			$this->db->pquery($sql3, array($this->id, $attachmentId));

			$sql2 = "UPDATE vtiger_users SET imagename='' WHERE id=?";
			$this->db->pquery($sql2, array($this->id));

			$sql4 = 'DELETE FROM vtiger_attachments WHERE attachmentsid=?';
			$this->db->pquery($sql4, array($attachmentId));
		}
	}

	/** Function to delete an entity with given Id */
	public function trash($module, $id)
	{
		$this->mark_deleted($id);
	}

	/**
	 * Transform owner ship and delete
	 * @param int $userId
	 * @param array $transformToUserId
	 */
	public function transformOwnerShipAndDelete($userId, $transformToUserId)
	{
		$eventHandler = new \App\EventHandler();
		$eventHandler->setParams(['userId' => $userId, 'transformToUserId' => $transformToUserId]);
		$eventHandler->setModuleName('Users');
		$eventHandler->trigger('UsersBeforeDelete');

		vtws_transferOwnership($userId, $transformToUserId);
		//updating the vtiger_users table;
		\App\Db::getInstance()->createCommand()
			->update('vtiger_users', [
				'status' => 'Inactive',
				'deleted' => 1,
				'date_modified' => date('Y-m-d H:i:s'),
				'modified_user_id' => \App\User::getCurrentUserRealId()
				], ['id' => $userId])->execute();

		$eventHandler->trigger('UsersAfterDelete');
	}

	/**
	 * This function should be overridden in each module.  It marks an item as deleted.
	 * @param <type> $id
	 */
	public function mark_deleted($id)
	{
		$adb = \App\Database\PearDatabase::getInstance();
		$current_user = vglobal('current_user');
		$date_var = date('Y-m-d H:i:s');
		$query = "UPDATE vtiger_users set status=?,date_modified=?,modified_user_id=? where id=?";
		$adb->pquery($query, array('Inactive', $adb->formatDate($date_var, true),
			$current_user->id, $id), true, "Error marking record deleted: ");
	}


	/**
	 * Function to get the active admin user object
	 * @return Users - Active Admin User Instance
	 */
	public static function getActiveAdminUser()
	{
		$adminId = \App\User::getActiveAdminId();
		$user = \App\CRMEntity::getInstance('Users');
		$user->retrieveCurrentUserInfoFromFile($adminId);
		return $user;
	}
}
