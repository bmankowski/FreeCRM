<?php

namespace App\Modules\SMSNotifier;

class SMSNotifier extends SMSNotifierBase
{

	/**
	 * Check if there is active server configured.
	 *
	 * @return true if activer server is found, false otherwise.
	 */
	static function checkServer()
	{
		$provider = SMSNotifierManager::getActiveProviderInstance();
		return ($provider !== false);
	}

	/**
	 * Send SMS (Creates SMS Entity record, links it with related CRM record and triggers provider to send sms)
	 *
	 * @param string $message
	 * @param array  $tonumbers
	 * @param integer $ownerid User id to assign the SMS record
	 * @param mixed $linktoids List of CRM record id to link SMS record
	 * @param string $linktoModule Modulename of CRM record to link with (if not provided lookup it will be calculated)
	 */
	static function sendsms($message, $tonumbers, $ownerid = false, $linktoids = false, $linktoModule = false)
	{
		global $current_user, $adb;

		if ($ownerid === false) {
			if (isset($current_user) && !empty($current_user)) {
				$ownerid = $currentUser->id;
			} else {
				$ownerid = 1;
			}
		}

		$moduleName = 'SMSNotifier';
		$focus = \App\CRMEntity::getInstance($moduleName);

		$focus->column_fields['message'] = $message;
		$focus->column_fields['assigned_user_id'] = $ownerid;
		$focus->save($moduleName);

		if ($linktoids !== false) {

			if ($linktoModule !== false) {
				\App\Utils\Utils::relateEntities($focus, $moduleName, $focus->id, $linktoModule, $linktoids);
			} else {
				// Link modulename not provided (linktoids can belong to mix of module so determine proper modulename)
				$query = "SELECT setype,crmid FROM vtiger_crmentity WHERE crmid IN (%s)";
				$query = sprintf($query, \App\Utils\Utils::generateQuestionMarks($linktoids));
				$linkidsetypes = $adb->pquery($query, [$linktoids]);
				if ($linkidsetypes && $adb->num_rows($linkidsetypes)) {
					while ($linkidsetypesrow = $adb->fetch_array($linkidsetypes)) {
						\App\Utils\Utils::relateEntities($focus, $moduleName, $focus->id, $linkidsetypesrow['setype'], $linkidsetypesrow['crmid']);
					}
				}
			}
		}
		$responses = self::fireSendSMS($message, $tonumbers);
		$focus->processFireSendSMSResponse($responses);
	}

	/**
	 * Detect the related modules based on the entity relation information for this instance.
	 */
	public function detectRelatedModules()
	{

		$adb = \App\Database\PearDatabase::getInstance();
		$currentUser = \App\User\CurrentUser::get();

		// Pick the distinct modulenames based on related records.
		$result = $adb->pquery("SELECT distinct setype FROM vtiger_crmentity WHERE crmid in (
			SELECT relcrmid FROM vtiger_crmentityrel INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid=vtiger_crmentityrel.crmid
			WHERE vtiger_crmentity.crmid = ? && vtiger_crmentity.deleted=0)", array($this->id));

		$relatedModules = array();

		// Calculate the related module access (similar to getRelatedList API in DetailViewUtils.php)
		if ($result && $adb->num_rows($result)) {
			require('user_privileges/user_privileges_' . $currentUser->id . '.php');
			while ($resultrow = $adb->fetch_array($result)) {
				$accessCheck = false;
				$relatedTabId = \App\Module::getModuleId($resultrow['setype']);
				if ($relatedTabId == 0) {
					$accessCheck = true;
				} else {
					if ($profileTabsPermission[$relatedTabId] == 0) {
						if ($profileActionPermission[$relatedTabId][3] == 0) {
							$accessCheck = true;
						}
					}
				}

				if ($accessCheck) {
					$relatedModules[$relatedTabId] = $resultrow['setype'];
				}
			}
		}

		return $relatedModules;
	}

	protected function isUserOrGroup($id)
	{
		$adb = \App\Database\PearDatabase::getInstance();
		$result = $adb->pquery("SELECT 1 FROM vtiger_users WHERE id=?", array($id));
		if ($result && $adb->num_rows($result)) {
			return 'U';
		} else {
			return 'T';
		}
	}

	protected function smsAssignedTo()
	{
		$adb = \App\Database\PearDatabase::getInstance();

		// Determine the number based on Assign To
		$assignedtoid = $this->column_fields['assigned_user_id'];
		$type = $this->isUserOrGroup($assignedtoid);

		if ($type == 'U') {
			$userIds = array($assignedtoid);
		} else {
			require_once(ROOT_DIRECTORY . '/src/Utils/GetGroupUsers.php');
			$getGroupObj = new GetGroupUsers();
			$getGroupObj->getAllUsersInGroup($assignedtoid);
			$userIds = $getGroupObj->group_users;
		}

		$tonumbers = array();

		if (count($userIds) > 0) {
			$phoneSqlQuery = "select phone_mobile, id from vtiger_users WHERE status='Active' && id in(%s)";
			$phoneSqlQuery = sprintf($phoneSqlQuery, \App\Utils\Utils::generateQuestionMarks($userIds));
			$phoneSqlResult = $adb->pquery($phoneSqlQuery, [$userIds]);
			while ($phoneSqlResultRow = $adb->fetch_array($phoneSqlResult)) {
				$number = $phoneSqlResultRow['phone_mobile'];
				if (!empty($number)) {
					$tonumbers[] = $number;
				}
			}
		}

		if (!empty($tonumbers)) {
			$responses = self::fireSendSMS($this->column_fields['message'], $tonumbers);
			$this->processFireSendSMSResponse($responses);
		}
	}

	private function processFireSendSMSResponse($responses)
	{

		if (empty($responses))
			return;

		$adb = \App\Database\PearDatabase::getInstance();

		foreach ($responses as $response) {
			$responseID = '';
			$responseStatus = '';
			$responseStatusMessage = '';

			$needlookup = 1;
			if ($response['error']) {
				$responseStatus = ISMSProvider::MSG_STATUS_FAILED;
				$needlookup = 0;
			} else {
				$responseID = $response['id'];
				$responseStatus = $response['status'];
			}

			if (isset($response['statusmessage'])) {
				$responseStatusMessage = $response['statusmessage'];
			}
			$adb->pquery("INSERT INTO vtiger_smsnotifier_status(smsnotifierid,tonumber,status,statusmessage,smsmessageid,needlookup) VALUES(?,?,?,?,?,?)", array($this->id, $response['to'], $responseStatus, $responseStatusMessage, $responseID, $needlookup)
			);
		}
	}

	static function smsquery($record)
	{
		$adb = \App\Database\PearDatabase::getInstance();
		$result = $adb->pquery("SELECT * FROM vtiger_smsnotifier_status WHERE smsnotifierid = ? && needlookup = 1", array($record));
		if ($result && $adb->num_rows($result)) {
			$provider = SMSNotifierManager::getActiveProviderInstance();

			while ($resultrow = $adb->fetch_array($result)) {
				$messageid = $resultrow['smsmessageid'];

				$response = $provider->query($messageid);

				if ($response['error']) {
					$responseStatus = ISMSProvider::MSG_STATUS_FAILED;
					$needlookup = $response['needlookup'];
				} else {
					$responseStatus = $response['status'];
					$needlookup = $response['needlookup'];
				}

				$responseStatusMessage = '';
				if (isset($response['statusmessage'])) {
					$responseStatusMessage = $response['statusmessage'];
				}

				$adb->pquery("UPDATE vtiger_smsnotifier_status SET status=?, statusmessage=?, needlookup=? WHERE smsmessageid = ?", array($responseStatus, $responseStatusMessage, $needlookup, $messageid));
			}
		}
	}

	static function fireSendSMS($message, $tonumbers)
	{
		
		$provider = SMSNotifierManager::getActiveProviderInstance();
		if ($provider) {
			return $provider->send($message, $tonumbers);
		}
	}

	static function getSMSStatusInfo($record)
	{
		$adb = \App\Database\PearDatabase::getInstance();
		$results = array();
		$qresult = $adb->pquery("SELECT * FROM vtiger_smsnotifier_status WHERE smsnotifierid=?", array($record));
		if ($qresult && $adb->num_rows($qresult)) {
			while ($resultrow = $adb->fetch_array($qresult)) {
				$results[] = $resultrow;
			}
		}
		return $results;
	}
