<?php

namespace App\Modules\DocumentTemplates\Models;

class Record extends \App\Modules\Base\Models\Record
{
	public function getId()
	{
		$id = $this->get('documenttemplatesid');
		if ($id !== null && $id !== '') {
			return (int) $id;
		}
		return parent::getId();
	}

	public function getDetailViewUrl()
	{
		return 'index.php?module=DocumentTemplates&view=Edit&record=' . $this->getId() . '&mode=Step1';
	}

	protected static function getDefaultData($moduleName = 'Vtiger'): array
	{
		return [
			'module_name' => $moduleName,
			'header_content' => '',
			'body_content' => '',
			'footer_content' => '',
			'status' => 1,
			'primary_name' => '',
			'secondary_name' => '',
			'meta_author' => '',
			'meta_creator' => '',
			'meta_keywords' => '',
			'metatags_status' => 1,
			'meta_subject' => '',
			'meta_title' => '',
			'page_format' => 'A4',
			'margin_chkbox' => 1,
			'margin_top' => 0,
			'margin_bottom' => 0,
			'margin_left' => 0,
			'margin_right' => 0,
			'header_height' => 0,
			'footer_height' => 0,
			'page_orientation' => 'PLL_PORTRAIT',
			'language' => '',
			'filename' => '',
			'visibility' => 'PLL_LISTVIEW,PLL_DETAILVIEW',
			'default' => 0,
			'conditions' => '[]',
			'watermark_type' => 0,
			'watermark_text' => '',
			'watermark_size' => 0,
			'watermark_angle' => 0,
			'watermark_image' => '',
			'template_members' => '',
			'one_file' => 0,
		];
	}

	public static function getCleanInstance($moduleName = 'Vtiger'): DocumentTemplate
	{
		$pdf = new DocumentTemplate();
		$pdf->setData(self::getDefaultData($moduleName));
		return $pdf;
	}

	public static function saveWizardStep(DocumentTemplate $pdfModel, $step = 1)
	{
		$db = \App\Db\Db::getInstance('admin');
		$table = DocumentTemplate::$baseTable;
		$index = DocumentTemplate::$baseIndex;
		$step = (int) $step;

		if ($step >= 2 && $step <= 6) {
			$fields = [];
			foreach (Module::getFieldsByStep($step) as $field) {
				$value = $pdfModel->get($field);
				$fields[$field] = $field === 'conditions' && is_array($value)
					? json_encode($value)
					: $value;
			}
			$db->createCommand()->update($table, $fields, [$index => $pdfModel->getId()])->execute();
			\App\Cache\Cache::delete('DocumentTemplateModel', $pdfModel->getId());
			return $pdfModel->getId();
		}

		$stepFields = Module::getFieldsByStep(1);
		if (!$pdfModel->getId()) {
			$params = self::getDefaultData((string) $pdfModel->get('module_name'));
			foreach ($stepFields as $field) {
				$params[$field] = $pdfModel->get($field);
			}
			$db->createCommand()->insert($table, $params)->execute();
			$newId = (int) $db->getLastInsertID();
			$pdfModel->set($index, $newId);
		} else {
			$fields = [];
			foreach ($stepFields as $field) {
				$fields[$field] = $pdfModel->get($field);
			}
			$db->createCommand()->update($table, $fields, [$index => $pdfModel->getId()])->execute();
		}
		\App\Cache\Cache::delete('DocumentTemplateModel', $pdfModel->getId());
		return $pdfModel->getId();
	}

	public function getDisplayValue($fieldName, $recordId = false, $rawText = false)
	{
		if ($fieldName === 'conditions') {
			$conditions = $this->get('conditions');
			return is_array($conditions) && $conditions !== [] ? (string) count($conditions) : '';
		}
		if ($fieldName === 'template_members') {
			$members = (string) $this->get('template_members');
			return $members !== '' ? str_replace(',', ', ', $members) : '';
		}
		if ($fieldName === 'status' || $fieldName === 'default') {
			$value = $this->get($fieldName);
			return $value
				? \App\Runtime\Vtiger_Language_Handler::translate('LBL_YES', 'DocumentTemplates')
				: \App\Runtime\Vtiger_Language_Handler::translate('LBL_NO', 'DocumentTemplates');
		}
		$module = $this->getModule();
		if ($module) {
			$fieldModel = $module->getField($fieldName);
			if ($fieldModel) {
				return $fieldModel->getDisplayValue($this->get($fieldName), $recordId, $this, $rawText);
			}
		}
		$value = $this->get($fieldName);
		return $value !== null && $value !== '' ? (string) $value : '';
	}

	public function get($key)
	{
		if ($key === 'conditions') {
			$value = parent::get($key);
			if (is_string($value) && $value !== '') {
				$decoded = json_decode($value, true);
				return is_array($decoded) ? $decoded : [];
			}
			return is_array($value) ? $value : [];
		}
		return parent::get($key);
	}

	public function getRecordLinks()
	{
		$links = [];
		$recordLinks = [
			[
				'linktype' => 'LISTVIEWRECORD',
				'linklabel' => 'LBL_EXPORT_RECORD',
				'linkurl' => 'index.php?module=DocumentTemplates&action=ExportTemplate&id=' . $this->getId(),
				'linkicon' => 'glyphicon glyphicon-export',
			],
			[
				'linktype' => 'LISTVIEWRECORD',
				'linklabel' => 'LBL_DUPLICATE_RECORD',
				'linkurl' => 'index.php?module=DocumentTemplates&action=DuplicateTemplate&id=' . $this->getId(),
				'linkicon' => 'glyphicon glyphicon-duplicate',
			],
		];
		foreach ($recordLinks as $recordLink) {
			$links[] = \App\Modules\Base\Models\Link::getInstanceFromValues($recordLink);
		}
		return $links;
	}

	public static function applyDocumentLayoutFromDynamicId(\App\Modules\Base\Models\PDF $pdfModel, int $dynamicId): void
	{
		if ($dynamicId <= 0 || !$pdfModel->getId()) {
			return;
		}
		$row = (new \App\Db\Query())
			->from('u_yf_templateelements')
			->where([
				'templateelementsid' => $dynamicId,
				'status' => 1,
				'type' => 'PLL_DOCUMENT_LAYOUT',
			])
			->one();
		if (!$row) {
			return;
		}
		$moduleName = (string) $pdfModel->get('module_name');
		$rowModule = (string) ($row['module_name'] ?? '');
		if ($rowModule !== '' && $rowModule !== $moduleName) {
			return;
		}
		$tplLang = (string) ($pdfModel->get('language') ?? '');
		$rowLang = (string) ($row['language'] ?? '');
		if ($rowLang !== '' && $tplLang !== '' && $rowLang !== $tplLang) {
			return;
		}
		$parts = \App\Modules\TemplateElements\Models\Record::getLayoutParts($row);
		$db = \App\Db\Db::getInstance('admin');
		$db->createCommand()->update('u_yf_documenttemplates', [
			'header_content' => $parts['layout_header'],
			'body_content' => $parts['layout_body'],
			'footer_content' => $parts['layout_footer'],
		], ['documenttemplatesid' => $pdfModel->getId()])->execute();
		$pdfModel->set('header_content', $parts['layout_header']);
		$pdfModel->set('body_content', $parts['layout_body']);
		$pdfModel->set('footer_content', $parts['layout_footer']);
	}

	public static function transformAdvanceFilterToWorkFlowFilter($recordModel): void
	{
		$conditions = $recordModel->get('conditions');
		$wfCondition = [];
		if (!empty($conditions) && is_array($conditions)) {
			foreach ($conditions as $index => $condition) {
				$columns = $condition['columns'] ?? [];
				if ($index == '1' && empty($columns)) {
					$wfCondition[] = [
						'fieldname' => '', 'operation' => '', 'value' => '', 'valuetype' => '',
						'joincondition' => '', 'groupid' => '0',
					];
				}
				if (!empty($columns) && is_array($columns)) {
					foreach ($columns as $column) {
						$wfCondition[] = [
							'fieldname' => $column['columnname'],
							'operation' => $column['comparator'],
							'value' => $column['value'],
							'valuetype' => $column['valuetype'],
							'joincondition' => $column['column_condition'],
							'groupjoin' => $condition['condition'],
							'groupid' => $column['groupid'],
						];
					}
				}
			}
		}
		$recordModel->set('conditions', $wfCondition);
	}

	public static function encodeConditionsForDb($recordModel): void
	{
		$conditions = $recordModel->get('conditions');
		if (is_array($conditions)) {
			$recordModel->set('conditions', json_encode($conditions));
		}
	}

	public static function deleteWatermark(int $recordId): bool
	{
		$pdfModel = DocumentTemplate::getInstanceById($recordId);
		if (!$pdfModel) {
			return false;
		}
		$watermarkImage = $pdfModel->get('watermark_image');
		\App\Db\Db::getInstance('admin')->createCommand()
			->update('u_yf_documenttemplates', ['watermark_image' => null], ['documenttemplatesid' => $recordId])
			->execute();
		if ($watermarkImage && file_exists($watermarkImage)) {
			return unlink($watermarkImage);
		}
		return false;
	}
}
