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

namespace App\Modules\HelpDesk\Actions;

use App\Modules\HelpDesk\Models\DetailView;
use App\Modules\HelpDesk\Models\Record;

class TicketWorkflowAjax extends \App\Base\Controllers\BaseActionController
{
	public function checkPermission(\App\Http\Vtiger_Request $request): void
	{
		$recordId = (int) $request->get('record');
		$mode = (string) $request->get('mode');
		if ($recordId <= 0 || !in_array($mode, ['done', 'not_working', 'accept'], true)) {
			throw new \App\Exceptions\NoPermittedToRecord('LBL_PERMISSION_DENIED');
		}

		$recordModel = Record::getInstanceById($recordId, 'HelpDesk');
		if (!$recordModel->isEditable()) {
			throw new \App\Exceptions\NoPermittedToRecord('LBL_PERMISSION_DENIED');
		}

		$allowed = match ($mode) {
			'done' => DetailView::canMarkDone($recordModel),
			'not_working' => DetailView::canReportNotWorking($recordModel),
			'accept' => DetailView::canAccept($recordModel),
			default => false,
		};
		if (!$allowed) {
			throw new \App\Exceptions\NoPermittedToRecord('LBL_PERMISSION_DENIED');
		}
	}

	public function process(\App\Http\Vtiger_Request $request): void
	{
		$recordId = (int) $request->get('record');
		$mode = (string) $request->get('mode');
		$recordModel = Record::getInstanceById($recordId, 'HelpDesk');

		if ($mode === 'done') {
			$solution = trim((string) $request->getForHtml('solution', ''));
			$businessId = $this->requireOwnerId($recordModel, 'business_id');
			if ($businessId === null) {
				return;
			}
			$recordModel->set('ticketstatus', DetailView::STATUS_FOR_APPROVAL);
			$recordModel->set('assigned_user_id', $businessId);
			if ($solution !== '') {
				$recordModel->set('solution', $solution);
			}
			$recordModel->save();
			$this->addCommentIfPresent($recordModel, (string) $request->getRaw('comment'));
		} elseif ($mode === 'not_working') {
			$developerId = $this->requireOwnerId($recordModel, 'developer_id');
			if ($developerId === null) {
				return;
			}
			$recordModel->set('ticketstatus', DetailView::STATUS_IN_PROGRESS);
			$recordModel->set('assigned_user_id', $developerId);
			$recordModel->save();
			$this->addCommentIfPresent($recordModel, (string) $request->getRaw('comment'));
		} else {
			$businessId = $this->requireOwnerId($recordModel, 'business_id');
			if ($businessId === null) {
				return;
			}
			$recordModel->set('ticketstatus', DetailView::STATUS_CLOSED);
			$recordModel->set('assigned_user_id', $businessId);
			$recordModel->save();
			$this->addCommentIfPresent($recordModel, (string) $request->getRaw('comment'));
		}

		$this->emitResult(['success' => true, 'message' => 'LBL_TICKET_WORKFLOW_SAVED']);
	}

	public function validateRequest(\App\Http\Vtiger_Request $request): bool
	{
		return $request->validateWriteAccess();
	}

	private function requireOwnerId(Record $recordModel, string $fieldName): ?int
	{
		$ownerId = (int) $recordModel->get($fieldName);
		if ($ownerId <= 0) {
			$this->emitResult(['success' => false, 'message' => 'LBL_TICKET_ROLE_MISSING']);
			return null;
		}

		return $ownerId;
	}

	private function addCommentIfPresent(Record $ticket, string $content): void
	{
		$content = trim($content);
		if ($content === '') {
			return;
		}

		$currentUserId = (int) (\App\User\CurrentUser::getId() ?? 0);
		$comment = \App\Modules\Base\Models\Record::getCleanInstance('ModComments');
		$comment->set('assigned_user_id', $currentUserId);
		$comment->set('related_to', $ticket->getId());
		$comment->set('commentcontent', $content);
		$comment->save();
	}

	private function emitResult(array $payload): void
	{
		$response = new \App\Http\Vtiger_Response();
		$response->setResult($payload);
		$response->emit();
	}
}
