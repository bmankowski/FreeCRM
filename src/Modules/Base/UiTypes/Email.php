<?php

namespace App\Modules\Base\UiTypes;

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */

class Email extends BaseUiType
{

	/**
	 * Function to get the Template name for the current UI Type object
	 * @return string - Template Name
	 */
	public function getTemplateName()
	{
		return 'uitypes/Email.tpl';
	}

	public function getDisplayValue($value, $recordId = false, $recordInstance = false, $rawText = false)
	{
		$rawValue = \is_string($value) ? $value : (\is_scalar($value) ? (string) $value : '');
		if ($rawValue !== '' && !$rawText) {
			$moduleName = $this->get('field')->getModuleName();
			$fieldName = $this->get('field')->get('name');
			$value = \vtlib\Functions::textLength($rawValue);
			if (\App\Core\AppConfig::main('isActiveSendingMails') && $recordId && $moduleName !== 'Users') {
				$composeAllowed = \App\Email\Mailer::isRecipientAllowedByAllowlist($rawValue);
				if ($composeAllowed) {
					$url = \App\Modules\Mail\Models\Module::getComposeUrl($moduleName, (int) $recordId, $rawValue);
					$value = "<a class=\"cursorPointer sendMailBtn\" href=\"$url\" title=\""
						. \App\Modules\Base\Helpers\Util::toSafeHTML(\App\Runtime\Vtiger_Language_Handler::translate('LBL_SEND_EMAIL'))
						. "\">$value</a>";
				} else {
					$blockedTitle = \App\Modules\Base\Helpers\Util::toSafeHTML(
						\App\Runtime\Vtiger_Language_Handler::translate('LBL_MAIL_RECIPIENT_NOT_ALLOWED', 'Mail')
					);
					$value = "<span class=\"text-muted\" title=\"$blockedTitle\">$value</span>";
				}
			} else {
				if ($moduleName == 'Users' && $fieldName == 'user_name') {
					$value = "<a class='cursorPointer' href='mailto:" . $rawValue . "'>" . $value . "</a>";
				} else {
					$value = "<a class='emailField cursorPointer'  href='mailto:" . $rawValue . "'>" . $value . "</a>";
				}
			}
		}
		return $value;
	}
}
