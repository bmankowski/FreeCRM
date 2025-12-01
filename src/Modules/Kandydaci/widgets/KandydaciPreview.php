<?php

/**
 * Vtiger summary widget class.
 *
 * @package Widget
 *
 * @copyright YetiForce S.A.
 * @license YetiForce Public License 6.5 (licenses/LicenseEN.txt or yetiforce.com)
 */
class Kandydaci_KandydaciPreview_Widget extends Vtiger_Basic_Widget
{
	public function getWidget()
	{
		$this->Config['tpl'] = 'KandydaciPreview.tpl';
		$kandydaciId = $this->Record;

		$this->Config['data']['kandydaciId'] = $kandydaciId;

		return $this->Config;
	}

	public function getConfigTplName()
	{
		return 'KandydaciPreviewConfig';
	}
}
