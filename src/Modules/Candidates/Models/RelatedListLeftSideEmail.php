<?php

namespace App\Modules\Candidates\Models;

use App\Modules\Base\Models\Link;
use App\Modules\Base\Models\Record;

/**
 * FreeCRM — template-based email link for Candidates on related lists.
 *
 * @copyright FreeCRM
 * @license FreeCRM Public License 1.1
 */
class RelatedListLeftSideEmail
{
	/**
	 * @param array<string, mixed> $context
	 * @return Link[]
	 */
	public static function asLinks(int $recordId, Record $parentRecord, array $context): array
	{
		$moduleModel = \App\Modules\Base\Models\Module::getInstance('Candidates');
		if (!$moduleModel || !$moduleModel->isPermitted('MassComposeEmail')
			|| !\App\Core\AppConfig::main('isActiveSendingMails')
			|| !\App\Modules\Mail\Models\Module::canUserSend((int) \App\User\CurrentUser::getId())) {
			return [];
		}
		if (!self::recordHasEmail($recordId)) {
			return [];
		}
		return [Link::getInstanceFromValues([
			'linktype' => \App\Modules\Base\Models\RelatedListLeftSideLinks::LINK_TYPE,
			'linklabel' => 'LBL_SEND_EMAIL',
			'linkurl' => '#',
			'linkhref' => true,
			'linkicon' => 'glyphicon glyphicon-envelope',
			'linkclass' => 'js-send-email-modal',
			'relatedModuleName' => 'Candidates',
			'linkdata' => [
				'record-id' => $recordId,
				'module-name' => 'Candidates',
				'source-module' => $parentRecord->getModuleName(),
				'source-record' => (int) $parentRecord->getId(),
			],
		])];
	}

	public static function recordHasEmail(int $recordId): bool
	{
		return self::resolvePrimaryEmailField($recordId) !== null;
	}

	/**
	 * @return array{field: string, email: string}|null
	 */
	public static function resolvePrimaryEmailField(int $recordId): ?array
	{
		if (!\App\Utils\Utils::isRecordExists($recordId)) {
			return null;
		}
		$moduleModel = \App\Modules\Base\Models\Module::getInstance('Candidates');
		if (!$moduleModel) {
			return null;
		}
		$recordModel = \App\Modules\Base\Models\Record::getInstanceById($recordId, 'Candidates');
		foreach ($moduleModel->getFieldsByType('email') as $fieldName => $fieldModel) {
			if (!$fieldModel->isActiveField()) {
				continue;
			}
			$email = trim((string) $recordModel->get($fieldName));
			if ($email !== '') {
				return ['field' => $fieldName, 'email' => $email];
			}
		}

		return null;
	}
}
