<?php

namespace App\Modules\Kandydaci\Widgets;

/**
 * Vtiger summary widget class.
 *
 * @package Widget
 *
 * @copyright YetiForce S.A.
 * @license YetiForce Public License 6.5 (licenses/LicenseEN.txt or yetiforce.com)
 */
class KandydaciPreview extends \App\Modules\Base\Widgets\Basic
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
