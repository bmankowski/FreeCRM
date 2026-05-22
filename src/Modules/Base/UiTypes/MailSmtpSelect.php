<?php

namespace App\Modules\Base\UiTypes;

/**
 * UIType: SMTP server picker for email templates (values from s_yf_mail_smtp).
 *
 * @package FreeCRM
 * @license FreeCRM Public License 1.1
 * @author bmankowski@gmail.com
 */
class MailSmtpSelect extends BaseUiType
{
	/** {@inheritdoc} */
	public function getTemplateName()
	{
		return 'uitypes/MailSmtpSelect.tpl';
	}

	/** {@inheritdoc} */
	public function getListSearchTemplateName()
	{
		return 'uitypes/MailSmtpSelectFieldSearchView.tpl';
	}

	/**
	 * @return array<int, array{name: string, default: bool}>
	 */
	public function getPicklistValues()
	{
		$list = [];
		foreach (\App\Email\Mail::getAll() as $id => $smtp) {
			$label = $smtp['name'] ?? ('SMTP #' . $id);
			if (!empty($smtp['from_email'])) {
				$label .= ' (' . $smtp['from_email'] . ')';
			} elseif (!empty($smtp['host'])) {
				$label .= ' (' . $smtp['host'] . ')';
			}
			$list[$id] = [
				'name' => $label,
				'default' => !empty($smtp['default']),
			];
		}
		return $list;
	}

	/** {@inheritdoc} */
	public function getDisplayValue($value, $record = false, $recordInstance = false, $rawText = false)
	{
		if ($value === '' || $value === null) {
			return '';
		}
		$smtp = \App\Email\Mail::getSmtpById((int) $value);
		if (!$smtp) {
			return (string) $value;
		}
		$label = $smtp['name'] ?? '';
		if (!empty($smtp['from_email'])) {
			$label .= ' (' . $smtp['from_email'] . ')';
		} elseif (!empty($smtp['host'])) {
			$label .= ' (' . $smtp['host'] . ')';
		}
		return $label;
	}
}
