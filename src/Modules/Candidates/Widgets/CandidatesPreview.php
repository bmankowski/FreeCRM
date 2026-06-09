<?php

namespace App\Modules\Candidates\Widgets;

/**
 * Vtiger summary widget class.
 *
 * @package Widget
 *
 * @copyright YetiForce S.A.
 * @license YetiForce Public License 6.5 (licenses/LicenseEN.txt or yetiforce.com)
 */
class CandidatesPreview extends \App\Modules\Base\Widgets\Basic
{
	public function getWidget()
	{
		$this->Config['tpl'] = 'CandidatesPreview.tpl';
		$candidatesId = $this->Record;

		$this->Config['data']['candidatesId'] = $candidatesId;

		return $this->Config;
	}

	public function getConfigTplName()
	{
		return 'CandidatesPreviewConfig';
	}
}
