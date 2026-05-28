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

	public function getName(): string
	{
		$name = (string) $this->get('primary_name');
		return $name !== '' ? $name : (string) $this->get('filename');
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

	public static function getCleanInstance($moduleName = 'Vtiger'): self
	{
		$targetModule = ($moduleName !== '' && $moduleName !== 'DocumentTemplates') ? $moduleName : 'Vtiger';
		$instance = parent::getCleanInstance('DocumentTemplates');
		foreach (self::getDefaultData($targetModule) as $field => $value) {
			$instance->set($field, $value);
		}
		$userId = \App\User\CurrentUser::getId();
		if ($userId) {
			$instance->set('assigned_user_id', $userId);
		}
		return $instance;
	}

	public static function saveWizardStep(self $recordModel, int $step): int
	{
		self::applyDefaultsForNewRecord($recordModel);
		$recordModel->save();
		self::clearTemplateCaches($recordModel->getId());
		return $recordModel->getId();
	}

	public static function saveFullImport(self $recordModel): int
	{
		self::applyDefaultsForNewRecord($recordModel);
		$recordModel->save();
		self::clearTemplateCaches($recordModel->getId());
		return $recordModel->getId();
	}

	public function save($request = null)
	{
		parent::save($request);
		self::clearTemplateCaches($this->getId());
		return $this;
	}

	protected static function applyDefaultsForNewRecord(self $recordModel): void
	{
		if (!$recordModel->isNew()) {
			return;
		}
		$moduleName = (string) ($recordModel->get('module_name') ?: 'Vtiger');
		foreach (self::getDefaultData($moduleName) as $field => $value) {
			$current = $recordModel->get($field);
			if ($current === null || $current === '') {
				$recordModel->set($field, $value);
			}
		}
		if (!$recordModel->get('assigned_user_id')) {
			$userId = \App\User\CurrentUser::getId();
			if ($userId) {
				$recordModel->set('assigned_user_id', $userId);
			}
		}
	}

	protected static function clearTemplateCaches(int $recordId): void
	{
		if ($recordId <= 0) {
			return;
		}
		\App\Cache\Cache::delete('DocumentTemplateModel', $recordId);
		\App\Cache\Cache::delete('DocumentTemplateModel', $recordId);
		\App\Cache\Cache::delete('RecordModel', $recordId . ':DocumentTemplates');
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

	public static function applyDocumentLayoutFromDynamicId(self $recordModel, int $dynamicId): void
	{
		if ($dynamicId <= 0 || !$recordModel->getId()) {
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
		$moduleName = (string) $recordModel->get('module_name');
		$rowModule = (string) ($row['module_name'] ?? '');
		if ($rowModule !== '' && $rowModule !== $moduleName) {
			return;
		}
		$tplLang = (string) ($recordModel->get('language') ?? '');
		$rowLang = (string) ($row['language'] ?? '');
		if ($rowLang !== '' && $tplLang !== '' && $rowLang !== $tplLang) {
			return;
		}
		$parts = \App\Modules\TemplateElements\Models\Record::getLayoutParts($row);
		$recordModel->set('header_content', $parts['layout_header']);
		$recordModel->set('body_content', $parts['layout_body']);
		$recordModel->set('footer_content', $parts['layout_footer']);
		$recordModel->save();
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
		$recordModel = self::getInstanceById($recordId, 'DocumentTemplates');
		if (!$recordModel) {
			return false;
		}
		$watermarkImage = $recordModel->get('watermark_image');
		$recordModel->set('watermark_image', '');
		$recordModel->save();
		if ($watermarkImage && file_exists($watermarkImage)) {
			return unlink($watermarkImage);
		}
		return false;
	}
}
