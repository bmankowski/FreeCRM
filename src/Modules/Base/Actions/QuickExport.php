<?php
/* {[The file is published on the basis of YetiForce Public License that can be found in the following directory: licenses/License.html]} */


namespace App\Modules\Base\Actions;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xls;

class QuickExport extends \App\Base\Controllers\BaseActionController
{

	public function checkPermission(\App\Http\Vtiger_Request $request)
	{
		$currentUserPriviligesModel = \App\Modules\Users\Models\Privileges::getCurrentUserPrivilegesModel();
		$permissionModule = $request->getModule();
		if ('ExportRelatedToExcel' === $request->getMode()) {
			$relatedModule = $request->getByType('relatedModule', 2);
			if (\is_numeric($relatedModule)) {
				$relatedModule = \App\Utils\ModuleUtils::getModuleName((int) $relatedModule);
			}
			$relatedModule = \trim((string) $relatedModule);
			if ($relatedModule !== '') {
				$permissionModule = $relatedModule;
			}
			$parentModule = $request->getByType('parentModule', 2) ?: $request->getModule(false);
			$parentId = $request->getInteger('record');
			if ($parentId > 0 && !\App\Modules\Users\Models\Privileges::isPermitted($parentModule, 'DetailView', $parentId)) {
				throw new \App\Exceptions\NoPermitted('LBL_PERMISSION_DENIED');
			}
		}
		if (!$currentUserPriviligesModel->hasModuleActionPermission($permissionModule, 'QuickExportToExcel')) {
			throw new \App\Exceptions\NoPermitted('LBL_PERMISSION_DENIED');
		}
	}

	public function __construct()
	{
		$this->exposeMethod('ExportToExcel');
		$this->exposeMethod('ExportRelatedToExcel');
	}

	public function process(\App\Http\Vtiger_Request $request)
	{
		$mode = $request->getMode();

		if ($mode) {
			$this->invokeExposedMethod($mode, $request);
		}
	}

	public function ExportToExcel(\App\Http\Vtiger_Request $request)
	{
		$module = $request->getModule(false); //this is the type of things in the current view
		$filter = $request->get('viewname'); //this is the cvid of the current custom filter
		$recordIds = \App\Modules\Base\Actions\Mass::getRecordsListFromRequest($request); //this handles the 'all' situation.
		//set up our spreadsheet to write out to
		$workbook = new Spreadsheet();
		$worksheet = $workbook->getActiveSheet();
		$header_styles = [
			'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'E1E0F7']],
			'font' => ['bold' => true]
		];
		$row = 1;
		$col = 0;

		$queryGenerator = new \App\QueryField\QueryGenerator($module);
		$queryGenerator->initForCustomViewById($filter);
		$headers = $queryGenerator->getListViewFields();
		$customView = \App\Modules\CustomView\Models\Record::getInstanceById($filter);
		//get the column headers, they go in row 0 of the spreadsheet
		foreach ($headers as &$fieldsModel) {
			$this->setCellValueExplicitByColumnAndRow($worksheet, $col, $row, \App\Utils\ListViewUtils::decodeHtml(\App\Runtime\Vtiger_Language_Handler::translate($fieldsModel->getFieldLabel(), $module)), DataType::TYPE_STRING);
			$col++;
		}
		$row++;

		$targetModuleFocus = \App\Core\CRMEntity::getInstance($module);
		//ListViewController has lots of paging stuff and things we don't want
		//so lets just itterate across the list of IDs we have and get the field values
		foreach ($recordIds as $id) {
			$col = 0;
			$record = \App\Modules\Base\Models\Record::getInstanceById($id, $module);
			foreach ($headers as &$fieldsModel) {
				//depending on the uitype we might want the raw value, the display value or something else.
				//we might also want the display value sans-links so we can use strip_tags for that
				//phone numbers need to be explicit strings
				$value = $record->getDisplayValue($fieldsModel->getFieldName());
				switch ($fieldsModel->getUIType()) {
					case 25:
					case 7:
						if ($fieldsModel->getFieldName() === 'sum_time') {
							$this->setCellValueExplicitByColumnAndRow($worksheet, $col, $row, strip_tags($value), DataType::TYPE_STRING);
						} else {
							$this->setCellValueExplicitByColumnAndRow($worksheet, $col, $row, strip_tags($value), DataType::TYPE_NUMERIC);
						}
						break;
					case 71:
					case 72:
						$rawValue = $record->get($fieldsModel->getFieldName());
						$this->setCellValueExplicitByColumnAndRow($worksheet, $col, $row, strip_tags($rawValue), DataType::TYPE_NUMERIC);
						break;
					case 6://datetimes
					case 23:
					case 70:
						$this->setCellValueExplicitByColumnAndRow($worksheet, $col, $row, Date::PHPToExcel(strtotime($record->get($fieldsModel->getFieldName()))), DataType::TYPE_NUMERIC);
						$worksheet->getStyle($this->getCellCoordinate($col, $row))->getNumberFormat()->setFormatCode('DD/MM/YYYY HH:MM:SS'); //format the date to the users preference
						break;
					default:
						$this->setCellValueExplicitByColumnAndRow($worksheet, $col, $row, \App\Utils\ListViewUtils::decodeHtml(strip_tags($value)), DataType::TYPE_STRING);
				}
				$col++;
			}
			$row++;
		}

		//having written out all the data lets have a go at getting the columns to auto-size
		$col = 0;
		$row = 1;
		foreach ($headers as &$fieldsModel) {
			$cell = $worksheet->getCell($this->getCellCoordinate($col, $row));
			$worksheet->getStyle($cell->getCoordinate())->applyFromArray($header_styles);
			$worksheet->getColumnDimension($cell->getColumn())->setAutoSize(true);
			$col++;
		}

		$filename = \App\Runtime\Vtiger_Language_Handler::translate($module, $module) . '-' . \App\Runtime\Vtiger_Language_Handler::translate(\App\Utils\ListViewUtils::decodeHtml($customView->get('viewname')), $module) . ".xls";
		$this->streamSpreadsheet($workbook, $filename);
	}

	public function ExportRelatedToExcel(\App\Http\Vtiger_Request $request): void
	{
		$parentModule = $request->getByType('parentModule', 2) ?: $request->getModule(false);
		$parentId = $request->getInteger('record');
		$relatedModule = $request->getByType('relatedModule', 2);
		if (\is_numeric($relatedModule)) {
			$relatedModule = \App\Utils\ModuleUtils::getModuleName((int) $relatedModule);
		}
		$relatedModule = \trim((string) $relatedModule);
		$tabLabel = (string) $request->get('tab_label');
		$selectedIds = $this->resolveSelectedIds($request);

		if ($parentId <= 0 || $relatedModule === '' || $selectedIds === []) {
			throw new \App\Exceptions\AppException('LBL_SELECT_RECORD');
		}

		$parentRecord = \App\Modules\Base\Models\Record::getInstanceById($parentId, $parentModule);
		$relationListView = \App\Modules\Base\Models\RelationListView::getInstance($parentRecord, $relatedModule, $tabLabel !== '' ? $tabLabel : false);
		$headerFields = $relationListView->getHeaders();
		$records = $relationListView->getEntriesByIds($selectedIds);

		if ($records === []) {
			throw new \App\Exceptions\AppException('LBL_SELECT_RECORD');
		}

		$workbook = new Spreadsheet();
		$worksheet = $workbook->getActiveSheet();
		$headerStyles = [
			'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'E1E0F7']],
			'font' => ['bold' => true],
		];
		$row = 1;
		$col = 0;

		foreach ($headerFields as $fieldModel) {
			$labelModule = $fieldModel->getModuleName() ?: $relatedModule;
			$label = \App\Runtime\Vtiger_Language_Handler::translate($fieldModel->getFieldLabel(), $labelModule);
			$this->setCellValueExplicitByColumnAndRow(
				$worksheet,
				$col,
				$row,
				\App\Utils\ListViewUtils::decodeHtml($label),
				DataType::TYPE_STRING
			);
			++$col;
		}
		++$row;

		foreach ($records as $record) {
			$col = 0;
			foreach ($headerFields as $fieldModel) {
				$value = $this->getRelatedListExportCellValue($record, $fieldModel);
				$this->setCellValueExplicitByColumnAndRow(
					$worksheet,
					$col,
					$row,
					$value,
					DataType::TYPE_STRING
				);
				++$col;
			}
			++$row;
		}

		$col = 0;
		$row = 1;
		foreach ($headerFields as $fieldModel) {
			$cell = $worksheet->getCell($this->getCellCoordinate($col, $row));
			$worksheet->getStyle($cell->getCoordinate())->applyFromArray($headerStyles);
			$worksheet->getColumnDimension($cell->getColumn())->setAutoSize(true);
			++$col;
		}

		$tabSuffix = $tabLabel !== ''
			? \App\Runtime\Vtiger_Language_Handler::translate($tabLabel, $parentModule)
			: \App\Runtime\Vtiger_Language_Handler::translate($relatedModule, $relatedModule);
		$filename = \App\Runtime\Vtiger_Language_Handler::translate($relatedModule, $relatedModule) . '-' . $tabSuffix . '.xls';
		$this->streamSpreadsheet($workbook, $filename);
	}

	public function validateRequest(\App\Http\Vtiger_Request $request)
	{
		$request->validateWriteAccess();
	}

	/**
	 * @return int[]
	 */
	private function resolveSelectedIds(\App\Http\Vtiger_Request $request): array
	{
		$selectedIds = $request->get('selected_ids');
		if (\is_array($selectedIds) && $selectedIds !== []) {
			return $this->normalizeSelectedIds($selectedIds);
		}
		if (\is_string($selectedIds) && $selectedIds !== '') {
			$trimmed = trim($selectedIds);
			if (str_starts_with($trimmed, '[')) {
				$decoded = json_decode($trimmed, true);
				if (\is_array($decoded) && $decoded !== []) {
					return $this->normalizeSelectedIds($decoded);
				}
			}
		}

		$fromMass = \App\Modules\Base\Actions\Mass::getRecordsListFromRequest($request);
		if (\is_array($fromMass) && $fromMass !== []) {
			return $this->normalizeSelectedIds($fromMass);
		}

		return [];
	}

	/**
	 * @param array<int|string> $ids
	 * @return int[]
	 */
	private function normalizeSelectedIds(array $ids): array
	{
		$normalized = [];
		foreach ($ids as $id) {
			$intId = (int) $id;
			if ($intId > 0) {
				$normalized[$intId] = $intId;
			}
		}

		return array_values($normalized);
	}

	private function getRelatedListExportCellValue(
		\App\Modules\Base\Models\Record $record,
		\App\Modules\Base\Models\Field $fieldModel
	): string {
		$fieldName = $fieldModel->getName();
		if ($fieldModel->isNameField() || '4' === (string) $fieldModel->get('uitype')) {
			$value = $record->getDisplayValue($fieldName);
		} elseif ($fieldModel->get('fromOutsideList')) {
			$value = $fieldModel->getDisplayValue($record->get($fieldName));
		} else {
			$value = $record->getListViewDisplayValue($fieldName);
		}

		return \App\Utils\ListViewUtils::decodeHtml(strip_tags((string) $value));
	}

	private function streamSpreadsheet(Spreadsheet $workbook, string $filename): void
	{
		$tmpDir = \App\Core\AppConfig::main('tmp_dir');
		$tempFileName = tempnam(ROOT_DIRECTORY . DIRECTORY_SEPARATOR . $tmpDir, 'xls');
		$workbookWriter = new Xls($workbook);
		$workbookWriter->save($tempFileName);

		if (isset($_SERVER['HTTP_USER_AGENT']) && strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE')) {
			header('Pragma: public');
			header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		}

		header('Content-Type: application/x-msexcel');
		header('Content-Length: ' . @filesize($tempFileName));
		header("Content-Disposition: attachment; filename=\"$filename\"");

		$fp = fopen($tempFileName, 'rb');
		fpassthru($fp);
		fclose($fp);
		unlink($tempFileName);
	}

	private function setCellValueExplicitByColumnAndRow($worksheet, $column, $row, $value, $dataType)
	{
		$worksheet->setCellValueExplicit($this->getCellCoordinate($column, $row), $value, $dataType);
	}

	private function getCellCoordinate($column, $row)
	{
		return Coordinate::stringFromColumnIndex($column + 1) . $row;
	}
}
