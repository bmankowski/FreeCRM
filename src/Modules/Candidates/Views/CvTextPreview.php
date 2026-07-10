<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * @project FreeCRM
 * @author bmankowski@gmail.com
 * @copyright (c) FreeCRM
 * @license FreeCRM Public License 1.0
 */

declare(strict_types=1);

namespace App\Modules\Candidates\Views;

use App\Http\Vtiger_Request;
use App\Modules\Candidates\Exceptions\InvalidCvSkillsExpressionException;
use App\Modules\Candidates\Services\CvSkillsSearch;

class CvTextPreview extends \App\Modules\Base\Views\Index
{
	protected function showBodyHeader(): bool
	{
		return false;
	}

	public function checkPermission(Vtiger_Request $request): void
	{
		$recordId = $request->getInteger('record');
		if ($recordId <= 0) {
			throw new \App\Exceptions\NoPermittedToRecord('LBL_NO_PERMISSIONS_FOR_THE_RECORD');
		}
		if (!\App\Modules\Users\Models\Privileges::isPermitted('Candidates', 'DetailView', $recordId)) {
			throw new \App\Exceptions\NoPermittedToRecord('LBL_NO_PERMISSIONS_FOR_THE_RECORD');
		}
	}

	public function process(Vtiger_Request $request): void
	{
		$recordId = $request->getInteger('record');
		$record = \App\Modules\Base\Models\Record::getInstanceById($recordId, 'Candidates');
		$cvText = (string) $record->get('cv_text');
		$highlightRaw = trim((string) $request->get('highlight'));
		$skills = [];
		if ($highlightRaw !== '') {
			try {
				$skills = CvSkillsSearch::collectTermsForHighlight($highlightRaw);
			} catch (InvalidCvSkillsExpressionException) {
				$skills = [];
			}
		}

		$viewer = $this->getViewer($request);
		$viewer->assign('CV_TEXT_HTML', self::formatCvTextHtml($cvText, $skills));
		$viewer->assign('CANDIDATE_NAME', (string) $record->get('name'));
		$viewer->view('CvTextPreviewIframe.tpl', 'Candidates');
	}

	/**
	 * @param list<string> $skills
	 */
	private static function formatCvTextHtml(string $cvText, array $skills): string
	{
		if ($cvText === '') {
			return '';
		}

		$escaped = htmlspecialchars($cvText, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
		foreach ($skills as $skill) {
			if ($skill === '') {
				continue;
			}
			$escaped = (string) preg_replace(
				CvSkillsSearch::buildWordMatchPcrePattern($skill),
				'<mark class="cv-text-preview__mark">$0</mark>',
				$escaped
			);
		}

		return nl2br($escaped, false);
	}

	public function validateRequest(Vtiger_Request $request): bool
	{
		return $request->validateReadAccess();
	}
}
