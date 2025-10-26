<?php

namespace App\Modules\Faq\Models;

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Contributor(s): YetiForce.com.
 * *********************************************************************************** */

class Record extends \App\Modules\Base\Models\Record
{

	/**
	 * Function to get Instance of Faq Record Model using TroubleTicket RecordModel
	 * @param  \App\Modules\HelpDesk\Models\Record
	 * @return \App\Modules\Faq\Models\Record
	 */
	public static function getInstanceFromHelpDesk($parentRecordModel)
	{
		$recordModel = \App\Modules\Base\Models\Record::getCleanInstance('Faq');
		$fieldMappingList = \App\Modules\Faq\Models\Record::getTicketToFAQMappingFields();

		foreach ($fieldMappingList as $fieldMapping) {
			$ticketField = $fieldMapping['ticketField'];
			$faqField = $fieldMapping['faqField'];
			if (!empty($ticketField)) {
				$faqData[$faqField] = $parentRecordModel->get($ticketField);
			} else {
				$faqData[$faqField] = $fieldMapping['defaultValue'];
			}
		}
		$recordModel->setData($faqData);

		//Updating the answer of Faq
		$answer = $recordModel->get('faq_answer');
		if ($answer) {
			$answer = \App\Runtime\Vtiger_Language_Handler::translate('LBL_SOLUTION', 'Faq') . ":\r\n" . $answer;
		}

		$commentsList = $parentRecordModel->getCommentsList();
		if ($commentsList) {
			$answer .= "\r\n\r\n" . \App\Runtime\Vtiger_Language_Handler::translate('LBL_COMMENTS', 'Faq') . ":";
			foreach ($commentsList as $comment) {
				$answer .= "\r\n$comment";
			}
		}
		$recordModel->set('faq_answer', $answer);
		return $recordModel;
	}

	/**
	 * Function get List of Fields which are mapping from Truoble Tickets to FAQ
	 * @return array
	 */
	public static function getTicketToFAQMappingFields()
	{
		return [
			['ticketField' => 'ticket_title', 'faqField' => 'question', 'defaultValue' => ''],
			['ticketField' => 'product_id', 'faqField' => 'product_id', 'defaultValue' => ''],
			['ticketField' => 'solution', 'faqField' => 'faq_answer', 'defaultValue' => ''],
			['ticketField' => '', 'faqField' => 'faqcategories', 'defaultValue' => 'General'],
			['ticketField' => '', 'faqField' => 'faqstatus', 'defaultValue' => 'Draft']
		];
	}
}
