<?php

namespace App\Modules\Base\Actions;

class ReportIssue extends \App\Base\Controllers\BaseActionController
{
	public function checkPermission(\App\Http\Vtiger_Request $request)
	{
		if (!$request->getUser()) {
			throw new \App\Exceptions\AppException('LBL_PERMISSION_DENIED');
		}
		if (!\App\Modules\Users\Models\Privileges::isPermitted('HelpDesk', 'CreateView')) {
			throw new \App\Exceptions\NoPermitted('LBL_PERMISSION_DENIED');
		}
	}

	public function validateRequest(\App\Http\Vtiger_Request $request)
	{
		$request->validateWriteAccess();
	}

	public function process(\App\Http\Vtiger_Request $request)
	{
		$title = trim((string) $request->get('title'));
		$description = trim((string) $request->get('description'));
		if ($title === '' || $description === '') {
			throw new \App\Exceptions\AppException('LBL_REPORT_ISSUE_REQUIRED_FIELDS');
		}

		$contextRaw = $request->get('context');
		$context = is_array($contextRaw) ? $contextRaw : json_decode((string) $contextRaw, true);
		if (!is_array($context)) {
			$context = [];
		}

		$screenshotFile = null;
		if (!empty($_FILES['screenshot']) && is_array($_FILES['screenshot'])) {
			$screenshotFile = $_FILES['screenshot'];
		}

		$service = new \App\Modules\HelpDesk\Services\ReportIssueService();
		$result = $service->createFromReport(
			$title,
			$description,
			$context,
			$screenshotFile,
			$request->getUser()
		);

		/** @var \App\Modules\Base\Models\Record $record */
		$record = $result['record'];

		$response = new \App\Http\Vtiger_Response();
		$response->setResult([
			'success' => true,
			'record' => $record->getId(),
			'ticket_no' => $record->get('ticket_no'),
			'ticket_url' => $record->getDetailViewUrl(),
			'github_url' => $result['github_url'],
			'github_error' => $result['github_error'],
		]);
		$response->emit();
	}
}
