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

namespace App\Modules\Mail\Views;

class Detail extends \App\Modules\Base\Views\Index
{
	public function getBreadcrumbTitle(\App\Http\Vtiger_Request $request)
	{
		$id = $request->getInteger('record');
		if ($id <= 0) {
			return parent::getBreadcrumbTitle($request);
		}
		$message = \App\Modules\Mail\Models\Message::getById($id);
		if (!$message) {
			return parent::getBreadcrumbTitle($request);
		}
		$subject = trim((string) ($message['subject'] ?? ''));
		if ($subject === '') {
			$subject = '#' . $id;
		}
		if (mb_strlen($subject) > 80) {
			$subject = mb_substr($subject, 0, 77) . '...';
		}

		return $subject;
	}

	public function process(\App\Http\Vtiger_Request $request): void
	{
		$id = (int) $request->get('record');
		$message = \App\Modules\Mail\Models\Message::getById($id);
		if (!$message) {
			throw new \App\Exceptions\AppException('LBL_RECORD_NOT_FOUND');
		}
		$account = !empty($message['account_id']) ? \App\Modules\Mail\Models\Account::getById((int) $message['account_id']) : null;
		\App\Modules\Mail\Models\Acl::assert((int) $request->getUser()->getId(), \App\Modules\Mail\Models\Acl::ACTION_VIEW, [
			'message' => $message,
			'account' => $account,
		]);

		$config = \HTMLPurifier_Config::createDefault();
		$purifier = new \HTMLPurifier($config);
		$bodyHtml = !empty($message['body_html']) ? $purifier->purify($message['body_html']) : nl2br(htmlspecialchars((string) ($message['body_text'] ?? '')));

		$links = (new \App\Db\Query())->from('u_yf_mail_record_links')->where(['message_id' => $id])->all();
		$attachments = \App\Modules\Mail\Models\Attachment::getForMessage($id);
		$openedAt = \App\Modules\Mail\Models\Service::getFirstOpenedAt($id);
		$openedAtDisplay = $openedAt !== null
			? \App\Modules\Base\UiTypes\Datetime::getDateTimeValue($openedAt)
			: '';

		[$sourceModule, $sourceRecord] = $this->resolveSourceContext($request, $links);
		$userId = (int) $request->getUser()->getId();
		$prevUrl = '';
		$nextUrl = '';
		if ($sourceModule !== '' && $sourceRecord > 0) {
			$adjacent = \App\Modules\Mail\Models\Service::getAdjacentMessageIds($sourceModule, $sourceRecord, $id, $userId);
			if (!empty($adjacent['prev'])) {
				$prevUrl = \App\Modules\Mail\Models\Module::getMessageDetailUrl((int) $adjacent['prev'], $sourceModule, $sourceRecord);
			}
			if (!empty($adjacent['next'])) {
				$nextUrl = \App\Modules\Mail\Models\Module::getMessageDetailUrl((int) $adjacent['next'], $sourceModule, $sourceRecord);
			}
		}

		$viewer = $this->getViewer($request);
		$viewer->assign('BREADCRUMBS', $this->buildMailBreadcrumbs($message, $sourceModule, $sourceRecord));
		$viewer->assign('MODULE_MODEL', \App\Modules\Base\Models\Module::getInstance('Mail'));
		$viewer->assign('NO_PAGINATION', false);
		$viewer->assign('PREVIOUS_RECORD_URL', $prevUrl);
		$viewer->assign('NEXT_RECORD_URL', $nextUrl);
		$viewer->assign('MESSAGE', $message);
		$viewer->assign('BODY_HTML', $bodyHtml);
		$viewer->assign('LINKS', $links);
		$viewer->assign('ATTACHMENTS', $attachments);
		$viewer->assign('OPENED_AT_DISPLAY', $openedAtDisplay);
		$viewer->view('Detail.tpl', 'Mail');
	}

	/**
	 * @param array<int, array<string, mixed>> $links
	 * @return array{0: string, 1: int}
	 */
	private function resolveSourceContext(\App\Http\Vtiger_Request $request, array $links): array
	{
		$sourceModule = $request->getByType('sourceModule', 2);
		$sourceRecord = $request->getInteger('sourceRecord');
		if ($sourceModule !== '' && $sourceRecord > 0) {
			return [$sourceModule, $sourceRecord];
		}
		if ($links !== []) {
			return [(string) ($links[0]['crm_module'] ?? ''), (int) ($links[0]['crm_record_id'] ?? 0)];
		}

		return ['', 0];
	}

	/**
	 * @param array<string, mixed> $message
	 * @return array<int, array{name: string, url?: string}>
	 */
	private function buildMailBreadcrumbs(array $message, string $sourceModule, int $sourceRecord): array
	{
		$subject = trim((string) ($message['subject'] ?? ''));
		if ($subject === '') {
			$subject = '#' . (int) ($message['id'] ?? 0);
		}

		if ($sourceModule !== '' && $sourceRecord > 0) {
			$breadcrumbs = $this->getModuleMenuBreadcrumbs($sourceModule);
			$parentModel = \App\Modules\Base\Models\Module::getInstance($sourceModule);
			if ($parentModel) {
				$breadcrumbs[] = [
					'name' => \App\Runtime\Vtiger_Language_Handler::translate($sourceModule, $sourceModule),
					'url' => $parentModel->getDefaultUrl(),
				];
				$recordLabel = \vtlib\Functions::getCRMRecordLabel($sourceRecord);
				if ($recordLabel !== '') {
					$breadcrumbs[] = [
						'name' => $recordLabel,
						'url' => $parentModel->getDetailViewUrl($sourceRecord),
					];
				}
			}
			$breadcrumbs[] = [
				'name' => \App\Runtime\Vtiger_Language_Handler::translate('LBL_MAILS', 'Mail'),
				'url' => \App\Modules\Mail\Models\Module::getParentRelatedListUrl($sourceModule, $sourceRecord),
			];
			$breadcrumbs[] = ['name' => $subject];

			return $breadcrumbs;
		}

		return [
			['name' => \App\Runtime\Vtiger_Language_Handler::translate('Mail', 'Mail')],
			['name' => \App\Runtime\Vtiger_Language_Handler::translate('LBL_VIEW_DETAIL', 'Mail')],
			['name' => $subject],
		];
	}

	/**
	 * @return array<int, array{name: string, url?: string}>
	 */
	private function getModuleMenuBreadcrumbs(string $moduleName): array
	{
		$userPrivModel = $this->userPrivilegesModel;
		if (!$userPrivModel) {
			return [];
		}

		$menuPrivileges = \App\Modules\Base\Models\Menu::loadPrivilegeFile($userPrivModel->get('roleid'));
		$parentList = $menuPrivileges['parentList'];

		$parent = null;
		if (isset($parentList) && \is_array($parentList)) {
			foreach ($parentList as $parentItem) {
				if ($moduleName === ($parentItem['mod'] ?? null)) {
					$parent = $parentItem['parent'];
					break;
				}
			}
		}
		$parentMenu = \App\Modules\Base\Models\Menu::getParentMenu($parentList ?? [], $parent, $moduleName);

		return \array_reverse($parentMenu);
	}
}
