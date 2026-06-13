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

namespace App\Modules\EmailTemplates\Actions;

use App\Http\Vtiger_Request;
use App\Modules\EmailTemplates\Models\TemplateAttachment as TemplateAttachmentModel;

class TemplateAttachment extends \App\Base\Controllers\BaseActionController
{
	public function __construct()
	{
		parent::__construct();
		$this->exposeMethod('list');
		$this->exposeMethod('link');
		$this->exposeMethod('unlink');
	}

	public function checkPermission(Vtiger_Request $request): bool
	{
		if (!\App\Security\Privilege::isPermitted('EmailTemplates')) {
			throw new \App\Exceptions\NoPermitted('LBL_PERMISSION_DENIED');
		}
		return true;
	}

	public function process(Vtiger_Request $request): void
	{
		$mode = $request->getMode();
		if (!empty($mode) && $this->isMethodExposed($mode)) {
			$this->invokeExposedMethod($mode, $request);
			return;
		}
		throw new \App\Exceptions\AppException('LBL_PERMISSION_DENIED');
	}

	public function list(Vtiger_Request $request): void
	{
		$templateId = $request->getInteger('templateId');
		if ($templateId <= 0) {
			$templateId = $request->getInteger('record');
		}
		if ($templateId <= 0) {
			throw new \App\Exceptions\AppException('LBL_RECORD_NOT_FOUND');
		}
		if (!\App\Security\Privilege::isPermitted('EmailTemplates', 'DetailView', $templateId)) {
			throw new \App\Exceptions\NoPermitted('LBL_PERMISSION_DENIED');
		}

		$response = new \App\Http\Vtiger_Response();
		$response->setResult([
			'success' => true,
			'items' => TemplateAttachmentModel::listForTemplate($templateId),
			'limits' => self::limitsPayload(),
		]);
		$response->emit();
	}

	public function link(Vtiger_Request $request): void
	{
		$templateId = $request->getInteger('templateId');
		if ($templateId <= 0) {
			$templateId = $request->getInteger('record');
		}
		$documentIds = self::parseDocumentIds($request);
		if ($templateId <= 0 || $documentIds === []) {
			throw new \App\Exceptions\AppException('LBL_RECORD_NOT_FOUND');
		}
		if (!\App\Modules\Users\Models\Privileges::isPermitted('Documents', 'DetailView')) {
			throw new \App\Exceptions\NoPermitted('LBL_PERMISSION_DENIED');
		}

		TemplateAttachmentModel::link($templateId, $documentIds);

		$response = new \App\Http\Vtiger_Response();
		$response->setResult([
			'success' => true,
			'items' => TemplateAttachmentModel::listForTemplate($templateId),
		]);
		$response->emit();
	}

	public function unlink(Vtiger_Request $request): void
	{
		$templateId = $request->getInteger('templateId');
		if ($templateId <= 0) {
			$templateId = $request->getInteger('record');
		}
		$documentId = $request->getInteger('documentId');
		if ($templateId <= 0 || $documentId <= 0) {
			throw new \App\Exceptions\AppException('LBL_RECORD_NOT_FOUND');
		}

		TemplateAttachmentModel::unlink($templateId, $documentId);

		$response = new \App\Http\Vtiger_Response();
		$response->setResult([
			'success' => true,
			'items' => TemplateAttachmentModel::listForTemplate($templateId),
		]);
		$response->emit();
	}

	/**
	 * @return list<int>
	 */
	private static function parseDocumentIds(Vtiger_Request $request): array
	{
		$raw = $request->get('documentIds');
		if ($raw === null || $raw === '') {
			$single = $request->getInteger('documentId');
			return $single > 0 ? [$single] : [];
		}
		if (is_string($raw)) {
			$decoded = \App\Utils\Json::decode($raw);
			$raw = is_array($decoded) ? $decoded : explode(',', $raw);
		}
		if (!is_array($raw)) {
			return [];
		}
		$ids = [];
		foreach ($raw as $id) {
			$id = (int) $id;
			if ($id > 0) {
				$ids[] = $id;
			}
		}

		return array_values(array_unique($ids));
	}

	/**
	 * @return array<string, int>
	 */
	private static function limitsPayload(): array
	{
		return [
			'maxFileBytes' => \App\Modules\Mail\Models\ComposeAttachment::maxFileBytes(),
			'maxTotalBytes' => \App\Modules\Mail\Models\ComposeAttachment::maxTotalBytes(),
			'maxFiles' => \App\Modules\Mail\Models\ComposeAttachment::maxFiles(),
		];
	}
}
