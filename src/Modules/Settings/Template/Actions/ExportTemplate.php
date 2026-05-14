<?php

namespace App\Modules\Settings\Template\Actions;



/**
 * Export to XML Class for PDF Settings
 * @package YetiForce.Action
 * @license licenses/License.html
 * @author Maciej Stencel <m.stencel@yetiforce.com>
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */
class ExportTemplate extends \App\Modules\Settings\Base\Actions\Index
{

	public function process(\App\Http\Vtiger_Request $request)
	{
		$recordId = $request->get('id');
		$pdfModel = \App\Modules\Base\Models\PDF::getInstanceById($recordId);
		if ($pdfModel === false) {
			throw new \App\Exceptions\AppException(\App\Runtime\Vtiger_Language_Handler::translate('LBL_RECORD_NOT_FOUND', 'Vtiger'));
		}

		header('content-type: application/xml; charset=utf-8');
		header('Pragma: public');
		header('Cache-Control: private');
		header('Content-Disposition: attachment; filename=' . $recordId . '_pdftemplate.xml');
		header('Content-Description: PHP Generated Data');

		$xml = new \DOMDocument('1.0', 'utf-8');
		$xml->preserveWhiteSpace = false;
		$xml->formatOutput = true;

		$xmlTemplate = $xml->createElement('pdf_template');
		$xmlFields = $xml->createElement('fields');
		$xmlField = $xml->createElement('field');

		$cDataColumns = ['header_content', 'body_content', 'footer_content', 'conditions'];
		foreach (\App\Modules\Settings\Template\Models\Module::$allFields as $field) {
			if (in_array($field, $cDataColumns, true)) {
				$name = $xmlField->appendChild($xml->createElement($field));
				$cdataRaw = (string) $pdfModel->getRaw($field);
				$name->appendChild($xml->createCDATASection(html_entity_decode($cdataRaw, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, 'UTF-8')));
				continue;
			}
			if ($field === 'watermark_image') {
				$watermarkPath = (string) $pdfModel->getRaw($field);
				if ($watermarkPath !== '' && is_file($watermarkPath)) {
					$im = file_get_contents($watermarkPath);
					if ($im !== false) {
						$xmlField->appendChild($xml->createElement('imageblob', base64_encode($im)));
					}
				}
				$xmlField->appendChild($xml->createElement($field, $watermarkPath));
				continue;
			}
			$value = $pdfModel->get($field);
			if (is_array($value) || is_object($value)) {
				$value = json_encode($value);
			}
			$value = (string) $value;
			$xmlField->appendChild($xml->createElement($field, html_entity_decode($value, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, 'UTF-8')));
		}

		$xmlFields->appendChild($xmlField);
		$xmlTemplate->appendChild($xmlFields);
		$xml->appendChild($xmlTemplate);
		print $xml->saveXML();
	}
}
