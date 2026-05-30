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

namespace App\Modules\Settings\Recruitment\Actions;

class SaveAjax extends \App\Modules\Settings\Base\Views\IndexAjax
{
	public function __construct()
	{
		parent::__construct();
		$this->exposeMethod('saveTransitions');
	}

	public function saveTransitions(\App\Http\Vtiger_Request $request): void
	{
		$param = $request->get('param');
		$transitions = \is_array($param) ? ($param['transitions'] ?? []) : [];
		if (!\is_array($transitions)) {
			$transitions = [];
		}

		$pairs = [];
		foreach ($transitions as $transition) {
			if (!\is_array($transition)) {
				continue;
			}
			$pairs[] = [
				'from' => (string) ($transition['from'] ?? ''),
				'to' => (string) ($transition['to'] ?? ''),
			];
		}

		\App\Modules\ProjektyRekrutacyjne\Services\RecruitmentStatusTransition::saveMatrix($pairs);

		$response = new \App\Http\Vtiger_Response();
		$response->setResult([
			'success' => true,
			'message' => \App\Language::translate('LBL_SAVE_TRANSITIONS_SUCCESS', $request->getModule(false)),
		]);
		$response->emit();
	}
}
