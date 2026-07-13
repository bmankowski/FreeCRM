<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * @project FreeCRM
 * @author bmankowski@gmail.com
 * @copyright (c) FreeCRM
 * @license FreeCRM Public License 1.1
 */

declare(strict_types=1);

namespace App\Modules\RecruitmentApplication\Models;

class Field extends \App\Modules\Base\Models\Field
{
	public function getDisplayValue($value, $record = false, $recordInstance = false, $rawText = false)
	{
		if ($this->getName() !== 'cv_document_id') {
			return parent::getDisplayValue($value, $record, $recordInstance, $rawText);
		}

		$documentId = (int) $value;
		if ($documentId <= 0) {
			return '';
		}

		$document = \App\Modules\Documents\Models\Record::getInstanceById($documentId, 'Documents');
		if (!$document || !$document->getId()) {
			return '';
		}

		$label = $this->resolveCvDownloadLabel($document, $recordInstance);
		if ($rawText) {
			return $label;
		}

		if ((string) $document->get('location_type') !== 'internal' || !(int) $document->get('active')) {
			return parent::getDisplayValue($value, $record, $recordInstance, $rawText);
		}

		if (!\App\Security\Privilege::isPermitted('Documents', 'DetailView', $documentId)) {
			return $label;
		}

		$label = \vtlib\Functions::textLength($label, \App\Core\AppConfig::main('href_max_length'));
		$url = $document->getDownloadFileURL();
		$title = \App\Runtime\Vtiger_Language_Handler::translate('LBL_DOWNLOAD_FILE', 'Documents');

		return '<a href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '" title="'
			. htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '">'
			. htmlspecialchars($label, ENT_NOQUOTES, 'UTF-8') . '</a>';
	}

	private function resolveCvDownloadLabel(
		\App\Modules\Documents\Models\Record $document,
		\App\Modules\Base\Models\Record|false $recordInstance
	): string {
		if ($recordInstance) {
			$original = (string) $recordInstance->get('cv_original_filename');
			if ($original !== '') {
				return $original;
			}
		}

		$originalName = (string) $document->get('original_name');
		if ($originalName !== '') {
			return $originalName;
		}

		return (string) \App\Records\Record::getLabel((int) $document->getId());
	}
}
