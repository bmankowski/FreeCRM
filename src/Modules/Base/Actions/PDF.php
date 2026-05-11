<?php

namespace App\Modules\Base\Actions;

/**
 * Returns special functions for PDF Settings
 * @package YetiForce.Action
 * @license licenses/License.html
 * @author Maciej Stencel <m.stencel@yetiforce.com>
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 * @author Adrian Koń <a.kon@yetiforce.com>
 */
class PDF extends \App\Base\Controllers\BaseActionController
{

	public function checkPermission(\App\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		if (!\App\Modules\Users\Models\Privileges::isPermitted($moduleName, 'ExportPdf')) {
			throw new \App\Exceptions\NoPermitted('LBL_PERMISSION_DENIED');
		}
	}

	public function __construct()
	{
		parent::__construct();
		$this->exposeMethod('hasValidTemplate');
		$this->exposeMethod('validateRecords');
		$this->exposeMethod('generate');
	}

	public function process(\App\Http\Vtiger_Request $request)
	{
		$mode = $request->get('mode');
		if (!empty($mode)) {
			$this->invokeExposedMethod($mode, $request);
			return;
		}
	}

	public function validateRecords(\App\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$records = $request->get('records');
		$templates = $request->get('templates');
		$allRecords = count($records);
		$output = ['valid_records' => [], 'message' => \App\Runtime\Vtiger_Language_Handler::translate('LBL_VALID_RECORDS', $moduleName, 0, $allRecords)];

		if (!empty($templates) && count($templates) > 0) {
			foreach ($templates as $templateId) {
				$templateRecord = \App\Modules\Base\Models\PDF::getInstanceById($templateId);
				foreach ($records as $recordId) {
					if (!$templateRecord->checkFiltersForRecord(intval($recordId))) {
						if (($key = array_search($recordId, $records)) !== false) {
							unset($records[$key]);
						}
					}
				}
			}
			$selectedRecords = count($records);

			$output = ['valid_records' => $records, 'message' => \App\Runtime\Vtiger_Language_Handler::translate('LBL_VALID_RECORDS', $moduleName, $selectedRecords, $allRecords)];
		}
		$response = new \App\Http\Vtiger_Response();
		$response->setResult($output);
		$response->emit();
	}

	public function generate(\App\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$recordId = $request->get('record');
		$templateIds = $this->getTemplateIdsFromRequest($request);
		$singlePdf = $request->get('single_pdf') == 1 ? true : false;
		$emailPdf = $request->get('email_pdf') == 1 ? true : false;

		if (empty($templateIds)) {
			throw new \App\Exceptions\AppException(\App\Runtime\Vtiger_Language_Handler::translate('LBL_EXPORT_ERROR', $moduleName));
		}
		if (!is_array($recordId)) {
			$recordId = [$recordId];
		}
		$templateAmount = count($templateIds);
		$recordsAmount = count($recordId);
		$selectedOneTemplate = $templateAmount == 1 ? true : false;
		if ($selectedOneTemplate) {
			$template = \App\Modules\Base\Models\PDF::getInstanceById($templateIds[0]);
			$generateOnePdf = $template->get('one_pdf');
		}

		if ($selectedOneTemplate && $recordsAmount == 1) {
			if ($emailPdf) {
				$filePath = 'cache/pdf/' . $recordId[0] . '_' . time() . '.pdf';
				\App\Modules\Base\Models\PDF::exportToPdf($recordId[0], $moduleName, $templateIds[0], $filePath, 'F');
				if (file_exists($filePath)) {
					header('Location: index.php?module=OSSMail&view=compose&pdf_path=' . $filePath);
				} else {
					throw new \App\Exceptions\AppException(\App\Runtime\Vtiger_Language_Handler::translate('LBL_EXPORT_ERROR', 'Settings:PDF'));
				}
			} else {
				\App\Modules\Base\Models\PDF::exportToPdf($recordId[0], $moduleName, $templateIds[0]);
			}
		} else if ($selectedOneTemplate && $recordsAmount > 1 && $generateOnePdf) {
			\App\Modules\Base\Models\PDF::exportToPdf($recordId, $moduleName, $templateIds[0]);
		} else {
			if ($singlePdf) {
				$handlerClass = \App\Modules\Base\Models\PDF::getPdfRendererClass($moduleName);
				$pdf = new $handlerClass();
				$styles = '';
				$headers = '';
				$footers = '';
				$classes = '';
				$body = '';
				foreach ($recordId as $index => $record) {
					$templateIdsTemp = $templateIds;
					$pdf->setRecordId($recordId[0]);
					$pdf->setModuleName($moduleName);

					$firstTemplate = array_shift($templateIdsTemp);
					$template = \App\Modules\Base\Models\PDF::getInstanceById($firstTemplate);
					$template->setMainRecordId($record);
					$pdf->setLanguage($template->get('language'));
					$template->getParameters();

					$styles .= " @page template_{$record}_{$firstTemplate} {
						sheet-size: {$template->getFormat()};
						margin-top: {$template->get('margin_top')}mm;
						margin-left: {$template->get('margin_left')}mm;
						margin-right: {$template->get('margin_right')}mm;
						margin-bottom: {$template->get('margin_bottom')}mm;
						odd-header-name: html_Header_{$record}_{$firstTemplate};
						odd-footer-name: html_Footer_{$record}_{$firstTemplate};
					}";
					$html = '';

					$headers .= ' <htmlpageheader name="Header_' . $record . '_' . $firstTemplate . '">' . $template->getHeader() . '</htmlpageheader>';
					$footers .= ' <htmlpagefooter name="Footer_' . $record . '_' . $firstTemplate . '">' . $template->getFooter() . '</htmlpagefooter>';
					$classes .= ' div.page_' . $record . '_' . $firstTemplate . ' { page-break-before: always; page: template_' . $record . '_' . $firstTemplate . '; }';
					$body .= '<div class="page_' . $record . '_' . $firstTemplate . '">' . $template->getBody() . '</div>';

					foreach ($templateIdsTemp as $id) {
						$template = \App\Modules\Base\Models\PDF::getInstanceById($id);
						$template->setMainRecordId($record);
						$pdf->setLanguage($template->get('language'));

						// building parameters
						$parameters = $template->getParameters();

						$styles .= " @page template_{$record}_{$id} {
							sheet-size: {$template->getFormat()};
							margin-top: {$template->get('margin_top')}mm;
							margin-left: {$template->get('margin_left')}mm;
							margin-right: {$template->get('margin_right')}mm;
							margin-bottom: {$template->get('margin_bottom')}mm;
							odd-header-name: html_Header_{$record}_{$id};
							odd-footer-name: html_Footer_{$record}_{$id};
						}";
						$html = '';

						$headers .= ' <htmlpageheader name="Header_' . $record . '_' . $id . '">' . $template->getHeader() . '</htmlpageheader>';
						$footers .= ' <htmlpagefooter name="Footer_' . $record . '_' . $id . '">' . $template->getFooter() . '</htmlpagefooter>';
						$classes .= ' div.page_' . $record . '_' . $id . ' { page-break-before: always; page: template_' . $record . '_' . $id . '; }';
						$body .= '<div class="page_' . $record . '_' . $id . '">' . $template->getBody() . '</div>';
					}
				}
				$html = "<html><head><style>{$styles} {$classes}</style></head><body>{$headers} {$footers} {$body}</body></html>";
				$pdf->loadHTML($html);
				$pdf->setFileName(\App\Runtime\Vtiger_Language_Handler::translate('LBL_PDF_MANY_IN_ONE'));
				$pdf->output();
			} else {
				mt_srand(time());
				$postfix = time() . '_' . mt_rand(0, 1000);

				$pdfFiles = [];
				foreach ($templateIds as $id) {
					foreach ($recordId as $record) {
						$handlerClass = \App\Modules\Base\Models\PDF::getPdfRendererClass($moduleName);
						$pdf = new $handlerClass();
						$pdf->setTemplateId($id);
						$pdf->setRecordId($record);
						$pdf->setModuleName($moduleName);

						$template = \App\Modules\Base\Models\PDF::getInstanceById($id);
						$template->setMainRecordId($record);
						$pdf->setLanguage($template->get('language'));
						$pdf->setFileName($template->get('filename'));

						$pdf->parseParams($template->getParameters());

						$html = '';

						$pdf->setHeader('Header', $template->getHeader());
						$pdf->setFooter('Footer', $template->getFooter());
						$html = $template->getBody();

						$pdf->loadHTML($html);
						$pdfFileName = 'cache/pdf/' . $record . '_' . $pdf->getFileName() . '_' . $postfix . '.pdf';
						$pdf->output($pdfFileName, 'F');

						if (file_exists($pdfFileName)) {
							$pdfFiles[] = $pdfFileName;
						}
						unset($pdf, $template);
					}
				}

				if (!empty($pdfFiles)) {
					if (!empty($emailPdf)) {
						\App\Modules\Base\Models\PDF::attachToEmail($postfix);
					} else {
						\App\Modules\Base\Models\PDF::zipAndDownload($pdfFiles);
					}
				}
			}
		}
	}

	/**
	 * Returns selected PDF template IDs from JS-enhanced or plain form submits.
	 *
	 * @param \App\Http\Vtiger_Request $request
	 * @return int[]
	 */
	protected function getTemplateIdsFromRequest(\App\Http\Vtiger_Request $request)
	{
		$templateIds = $request->get('template');
		if (empty($templateIds) || '[]' === $templateIds) {
			$templateIds = $request->get('pdf_template');
		}
		if (!is_array($templateIds)) {
			$templateIds = explode(',', (string) $templateIds);
		}
		$templateIds = array_map('intval', $templateIds);
		return array_values(array_filter($templateIds));
	}

	/**
	 * Checks if given record has valid pdf template
	 * @param \App\Http\Vtiger_Request $request
	 * @return boolean true if valid template exists for this record
	 */
	public function hasValidTemplate(\App\Http\Vtiger_Request $request)
	{
		$recordId = $request->get('record');
		$moduleName = $request->get('modulename');
		$view = $request->get('view');

		$pdfModel = new \App\Modules\Base\Models\PDF();
		$pdfModel->setMainRecordId($recordId);
		$valid = $pdfModel->checkActiveTemplates($recordId, $moduleName, $view);
		$output = ['valid' => $valid];

		$response = new \App\Http\Vtiger_Response();
		$response->setResult($output);
		$response->emit();
	}
}
