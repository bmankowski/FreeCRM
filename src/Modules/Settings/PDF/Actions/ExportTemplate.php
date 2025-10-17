<?php

namespace App\Modules\Settings\PDF\Actions;



/**
 * Export to XML Class for PDF Settings
 * @package YetiForce.Action
 * @license licenses/License.html
 * @author Maciej Stencel <m.stencel@yetiforce.com>
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */
class ExportTemplate extends \App\Modules\Settings\Vtiger\Actions\Index
{

	public function process(\App\Http\Vtiger_Request $request)
	{
		$recordId = $request->get('id');
		$pdfModel = \App\Modules\Vtiger\Models\PDF::getInstanceById($recordId);

		header('content-type: application/xml; charset=utf-8');
		header('Pragma: public');
		header('Cache-Control: private');
		header('Content-Disposition: attachment; filename=' . $recordId . '_pdftemplate.xml');
		header('Content-Description: PHP Generated Data');

		$xml = new DOMDocument('1.0', 'utf-8');
		$xml->preserveWhiteSpace = false;
		$xml->formatOutput = true;

		$xmlTemplate = $xml->createElement('pdf_template');
		$xmlFields = $xml->createElement('fields');
		$xmlField = $xml->createElement('field');

		$cDataColumns = ['header_content', 'body_content', 'footer_content', 'conditions'];
		foreach (\App\Modules\Settings\PDF\Models\Module::$allFields as $field) {
			if (in_array($field, $cDataColumns)) {
				$name = $xmlField->appendChild($xml->createElement($field));
				$name->appendChild($xml->createCDATASection(html_entity_decode($pdfModel->getRaw($field))));
			} elseif ($field === 'watermark_image') {
				if (file_exists($pdfModel->get($field))) {
					$watermarkPath = $pdfModel->get($field);
					$im = file_get_contents($watermarkPath);
					$imData = base64_encode($im);

					$xmlColumn = $xml->createElement('imageblob', $imData);
					$xmlField->appendChild($xmlColumn);
					$value = $watermarkPath;
				} else {
					$value = '';
				}
				$xmlColumn = $xml->createElement($field, $value);
			} else {
				$value = $pdfModel->get($field);
				$xmlColumn = $xml->createElement($field, html_entity_decode($value, ENT_COMPAT, 'UTF-8'));
			}
			$xmlField->appendChild($xmlColumn);
		}

		$xmlFields->appendChild($xmlField);
		$xmlTemplate->appendChild($xmlFields);
		$xml->appendChild($xmlTemplate);
		print $xml->saveXML();
	}
}
