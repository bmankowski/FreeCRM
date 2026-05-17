<?php
/**
 * FreeCRM - Register DocumentTemplates and TemplateElements as entity modules (EmailTemplates pattern).
 *
 * Run via: yii migrate --migrationPath=migrations/Users/
 */

declare(strict_types=1);

use yii\db\Migration;
use yii\db\Query;

class m260520_000001_document_templates_entity_modules extends Migration
{
	private const TAB_DOCUMENT_TEMPLATES = 127;
	private const TAB_TEMPLATE_ELEMENTS = 128;
	private const TAB_EMAIL_TEMPLATES = 112;
	private const CV_DOCUMENT_TEMPLATES = 514;
	private const CV_TEMPLATE_ELEMENTS = 515;

	public function safeUp(): void
	{
		$this->migrateTemplateElementsData();
		$this->backfillCrmentityDocumentTemplates();
		$this->backfillCrmentityTemplateElements();
		$this->updateTabsAsEntity();
		$this->registerEntityNames();
		$this->registerDocumentTemplatesMetadata();
		$this->registerTemplateElementsMetadata();
		$this->cloneProfilesFromEmailTemplates(self::TAB_DOCUMENT_TEMPLATES);
		$this->cloneProfilesFromEmailTemplates(self::TAB_TEMPLATE_ELEMENTS);
		$this->registerCustomViews();
		$this->removeSettingsMenuEntries();
		$this->migrateWorkflowPdfTemplateProperty();
		$this->dropLegacyPdfTables();
		$this->refreshTabdata();
	}

	public function safeDown(): void
	{
		$this->delete('vtiger_customview', ['cvid' => [self::CV_DOCUMENT_TEMPLATES, self::CV_TEMPLATE_ELEMENTS]]);
		$this->delete('vtiger_entityname', ['tabid' => [self::TAB_DOCUMENT_TEMPLATES, self::TAB_TEMPLATE_ELEMENTS]]);
		$this->update('vtiger_tab', ['isentitytype' => 0], ['tabid' => [self::TAB_DOCUMENT_TEMPLATES, self::TAB_TEMPLATE_ELEMENTS]]);
		$this->delete('vtiger_crmentity', ['setype' => ['DocumentTemplates', 'TemplateElements']]);
	}

	private function migrateTemplateElementsData(): void
	{
		if ($this->db->getTableSchema('a_yf_pdf_dynamic_elements', true) === null) {
			return;
		}
		if ($this->db->getTableSchema('u_yf_templateelements', true) === null) {
			return;
		}
		$rows = (new Query())->from('a_yf_pdf_dynamic_elements')->all($this->db);
		foreach ($rows as $row) {
			$id = (int) $row['dynamicid'];
			unset($row['dynamicid']);
			$row['templateelementsid'] = $id;
			if (!(new Query())->from('u_yf_templateelements')->where(['templateelementsid' => $id])->exists($this->db)) {
				$this->insert('u_yf_templateelements', $row);
			}
		}
	}

	private function backfillCrmentityDocumentTemplates(): void
	{
		if ($this->db->getTableSchema('u_yf_documenttemplates', true) === null) {
			return;
		}
		$rows = (new Query())
			->select(['documenttemplatesid', 'primary_name'])
			->from('u_yf_documenttemplates')
			->all($this->db);
		$now = date('Y-m-d H:i:s');
		foreach ($rows as $row) {
			$id = (int) $row['documenttemplatesid'];
			if ((new Query())->from('vtiger_crmentity')->where(['crmid' => $id])->exists($this->db)) {
				continue;
			}
			$this->insert('vtiger_crmentity', [
				'crmid' => $id,
				'smcreatorid' => 1,
				'smownerid' => 1,
				'shownerid' => 0,
				'modifiedby' => 1,
				'setype' => 'DocumentTemplates',
				'description' => null,
				'createdtime' => $now,
				'modifiedtime' => $now,
				'presence' => 1,
				'deleted' => 0,
				'was_read' => 0,
				'private' => 0,
			]);
		}
	}

	private function backfillCrmentityTemplateElements(): void
	{
		if ($this->db->getTableSchema('u_yf_templateelements', true) === null) {
			return;
		}
		$rows = (new Query())
			->select(['templateelementsid'])
			->from('u_yf_templateelements')
			->all($this->db);
		$now = date('Y-m-d H:i:s');
		foreach ($rows as $row) {
			$id = (int) $row['templateelementsid'];
			if ((new Query())->from('vtiger_crmentity')->where(['crmid' => $id])->exists($this->db)) {
				continue;
			}
			$this->insert('vtiger_crmentity', [
				'crmid' => $id,
				'smcreatorid' => 1,
				'smownerid' => 1,
				'shownerid' => 0,
				'modifiedby' => 1,
				'setype' => 'TemplateElements',
				'description' => null,
				'createdtime' => $now,
				'modifiedtime' => $now,
				'presence' => 1,
				'deleted' => 0,
				'was_read' => 0,
				'private' => 0,
			]);
		}
	}

	private function updateTabsAsEntity(): void
	{
		$this->update('vtiger_tab', ['isentitytype' => 1], ['tabid' => [self::TAB_DOCUMENT_TEMPLATES, self::TAB_TEMPLATE_ELEMENTS]]);
	}

	private function registerEntityNames(): void
	{
		foreach ([
			[self::TAB_DOCUMENT_TEMPLATES, 'DocumentTemplates', 'u_yf_documenttemplates', 'primary_name', 'documenttemplatesid'],
			[self::TAB_TEMPLATE_ELEMENTS, 'TemplateElements', 'u_yf_templateelements', 'label', 'templateelementsid'],
		] as [$tabId, $module, $table, $field, $idCol]) {
			if ((new Query())->from('vtiger_entityname')->where(['tabid' => $tabId])->exists($this->db)) {
				continue;
			}
			$this->insert('vtiger_entityname', [
				'tabid' => $tabId,
				'modulename' => $module,
				'tablename' => $table,
				'fieldname' => $field,
				'entityidfield' => $idCol,
				'entityidcolumn' => $idCol,
				'searchcolumn' => $field,
				'turn_off' => 1,
				'sequence' => 0,
			]);
		}
	}

	private function registerDocumentTemplatesMetadata(): void
	{
		if ((new Query())->from('vtiger_field')->where(['tabid' => self::TAB_DOCUMENT_TEMPLATES])->exists($this->db)) {
			return;
		}
		$blocks = [
			470 => 'LBL_BASIC_INFORMATION',
			471 => 'LBL_PAGE_LAYOUT',
			472 => 'LBL_DOCUMENT_CONTENT',
			473 => 'LBL_CONDITIONS',
			474 => 'LBL_TEMPLATE_MEMBERS',
			475 => 'LBL_WATERMARK',
			476 => 'LBL_CUSTOM_INFORMATION',
		];
		foreach ($blocks as $blockId => $label) {
			$this->insertBlock(self::TAB_DOCUMENT_TEMPLATES, $blockId, $label, array_search($blockId, array_keys($blocks), true) + 1);
		}
		$fields = [
			[303101, 'primary_name', 'u_yf_documenttemplates', 2, 'LBL_PRIMARY_NAME', 470, 1, 'V~M'],
			[303102, 'secondary_name', 'u_yf_documenttemplates', 1, 'LBL_SECONDARY_NAME', 470, 2, 'V~O'],
			[303103, 'status', 'u_yf_documenttemplates', 56, 'LBL_STATUS', 470, 3, 'C~O'],
			[303104, 'module_name', 'u_yf_documenttemplates', 301, 'Module', 470, 4, 'V~M'],
			[303105, 'metatags_status', 'u_yf_documenttemplates', 56, 'LBL_METATAGS', 470, 5, 'C~O'],
			[303106, 'meta_title', 'u_yf_documenttemplates', 1, 'LBL_META_TITLE', 470, 6, 'V~O'],
			[303107, 'meta_subject', 'u_yf_documenttemplates', 1, 'LBL_META_SUBJECT', 470, 7, 'V~O'],
			[303108, 'meta_author', 'u_yf_documenttemplates', 1, 'LBL_META_AUTHOR', 470, 8, 'V~O'],
			[303109, 'meta_creator', 'u_yf_documenttemplates', 1, 'LBL_META_CREATOR', 470, 9, 'V~O'],
			[303110, 'meta_keywords', 'u_yf_documenttemplates', 1, 'LBL_META_KEYWORDS', 470, 10, 'V~O'],
			[303111, 'page_format', 'u_yf_documenttemplates', 1, 'LBL_PAGE_FORMAT', 471, 1, 'V~O'],
			[303112, 'margin_chkbox', 'u_yf_documenttemplates', 56, 'LBL_MAIN_MARGIN', 471, 2, 'C~O'],
			[303113, 'margin_top', 'u_yf_documenttemplates', 7, 'LBL_MARGIN_TOP', 471, 3, 'I~O'],
			[303114, 'margin_bottom', 'u_yf_documenttemplates', 7, 'LBL_MARGIN_BOTTOM', 471, 4, 'I~O'],
			[303115, 'margin_left', 'u_yf_documenttemplates', 7, 'LBL_MARGIN_LEFT', 471, 5, 'I~O'],
			[303116, 'margin_right', 'u_yf_documenttemplates', 7, 'LBL_MARGIN_RIGHT', 471, 6, 'I~O'],
			[303117, 'header_height', 'u_yf_documenttemplates', 7, 'LBL_HEADER_HEIGHT', 471, 7, 'I~O'],
			[303118, 'footer_height', 'u_yf_documenttemplates', 7, 'LBL_FOOTER_HEIGHT', 471, 8, 'I~O'],
			[303119, 'page_orientation', 'u_yf_documenttemplates', 1, 'LBL_PAGE_ORIENTATION', 471, 9, 'V~O'],
			[303120, 'language', 'u_yf_documenttemplates', 1, 'LBL_LANGUAGE', 471, 10, 'V~O'],
			[303121, 'filename', 'u_yf_documenttemplates', 1, 'LBL_FILENAME', 471, 11, 'V~O'],
			[303122, 'visibility', 'u_yf_documenttemplates', 1, 'LBL_VISIBILITY', 471, 12, 'V~O'],
			[303123, 'default', 'u_yf_documenttemplates', 56, 'LBL_DEFAULT', 471, 13, 'C~O'],
			[303124, 'one_file', 'u_yf_documenttemplates', 56, 'LBL_ONE_FILE', 471, 14, 'C~O'],
			[303125, 'header_content', 'u_yf_documenttemplates', 300, 'LBL_HEADER', 472, 1, 'V~O'],
			[303126, 'body_content', 'u_yf_documenttemplates', 300, 'LBL_BODY', 472, 2, 'V~O'],
			[303127, 'footer_content', 'u_yf_documenttemplates', 300, 'LBL_FOOTER', 472, 3, 'V~O'],
			[303128, 'conditions', 'u_yf_documenttemplates', 1, 'LBL_CONDITIONS', 473, 1, 'V~O', 'document_template_conditions'],
			[303129, 'template_members', 'u_yf_documenttemplates', 1, 'LBL_MEMBERS', 474, 1, 'V~O', 'document_template_members'],
			[303130, 'watermark_type', 'u_yf_documenttemplates', 7, 'LBL_WATERMARK_TYPE', 475, 1, 'I~O'],
			[303131, 'watermark_text', 'u_yf_documenttemplates', 1, 'LBL_WATERMARK_TEXT', 475, 2, 'V~O'],
			[303132, 'watermark_size', 'u_yf_documenttemplates', 7, 'LBL_WATERMARK_SIZE', 475, 3, 'I~O'],
			[303133, 'watermark_angle', 'u_yf_documenttemplates', 7, 'LBL_WATERMARK_ANGLE', 475, 4, 'I~O'],
			[303134, 'watermark_image', 'u_yf_documenttemplates', 1, 'LBL_WATERMARK_IMAGE', 475, 5, 'V~O', 'document_template_watermark'],
			[303135, 'smownerid', 'vtiger_crmentity', 53, 'Assigned To', 476, 1, 'V~M', '', 'assigned_user_id'],
			[303136, 'createdtime', 'vtiger_crmentity', 70, 'Created Time', 476, 2, 'DT~O'],
			[303137, 'modifiedtime', 'vtiger_crmentity', 70, 'Modified Time', 476, 3, 'DT~O'],
			[303138, 'smcreatorid', 'vtiger_crmentity', 53, 'Created By', 476, 4, 'V~O', '', 'created_user_id'],
			[303139, 'shownerid', 'vtiger_crmentity', 120, 'Share with users', 476, 5, 'V~O'],
			[303140, 'private', 'vtiger_crmentity', 56, 'FL_IS_PRIVATE', 476, 6, 'C~O'],
		];
		foreach ($fields as $field) {
			$this->insertField(self::TAB_DOCUMENT_TEMPLATES, $field);
		}
	}

	private function registerTemplateElementsMetadata(): void
	{
		if ((new Query())->from('vtiger_field')->where(['tabid' => self::TAB_TEMPLATE_ELEMENTS])->exists($this->db)) {
			return;
		}
		$blocks = [478 => 'LBL_BASIC_INFORMATION', 479 => 'LBL_CONTENT', 480 => 'LBL_CUSTOM_INFORMATION'];
		foreach ($blocks as $blockId => $label) {
			$this->insertBlock(self::TAB_TEMPLATE_ELEMENTS, $blockId, $label, array_search($blockId, array_keys($blocks), true) + 1);
		}
		$fields = [
			[303201, 'code', 'u_yf_templateelements', 1, 'LBL_CODE', 478, 1, 'V~M'],
			[303202, 'label', 'u_yf_templateelements', 1, 'LBL_LABEL', 478, 2, 'V~M'],
			[303203, 'type', 'u_yf_templateelements', 1, 'LBL_TYPE', 478, 3, 'V~O'],
			[303204, 'module_name', 'u_yf_templateelements', 301, 'Module', 478, 4, 'V~O'],
			[303205, 'language', 'u_yf_templateelements', 1, 'LBL_LANGUAGE', 478, 5, 'V~O'],
			[303206, 'status', 'u_yf_templateelements', 56, 'LBL_STATUS', 478, 6, 'C~O'],
			[303207, 'sequence', 'u_yf_templateelements', 7, 'LBL_SEQUENCE', 478, 7, 'I~O'],
			[303208, 'content', 'u_yf_templateelements', 300, 'LBL_CONTENT', 479, 1, 'V~O'],
			[303209, 'layout_header', 'u_yf_templateelements', 300, 'LBL_HEADER', 479, 2, 'V~O'],
			[303210, 'layout_body', 'u_yf_templateelements', 300, 'LBL_BODY', 479, 3, 'V~O'],
			[303211, 'layout_footer', 'u_yf_templateelements', 300, 'LBL_FOOTER', 479, 4, 'V~O'],
			[303212, 'description', 'u_yf_templateelements', 19, 'LBL_DESCRIPTION', 479, 5, 'V~O'],
			[303213, 'smownerid', 'vtiger_crmentity', 53, 'Assigned To', 480, 1, 'V~M', '', 'assigned_user_id'],
			[303214, 'createdtime', 'vtiger_crmentity', 70, 'Created Time', 480, 2, 'DT~O'],
			[303215, 'modifiedtime', 'vtiger_crmentity', 70, 'Modified Time', 480, 3, 'DT~O'],
		];
		foreach ($fields as $field) {
			$this->insertField(self::TAB_TEMPLATE_ELEMENTS, $field);
		}
	}

	private function insertBlock(int $tabId, int $blockId, string $label, int $sequence): void
	{
		if ((new Query())->from('vtiger_blocks')->where(['blockid' => $blockId])->exists($this->db)) {
			return;
		}
		$this->insert('vtiger_blocks', [
			'blockid' => $blockId,
			'tabid' => $tabId,
			'blocklabel' => $label,
			'sequence' => $sequence,
			'show_title' => 0,
			'visible' => 0,
			'create_view' => 0,
			'edit_view' => 0,
			'detail_view' => 0,
			'display_status' => 1,
			'iscustom' => 0,
		]);
	}

	/**
	 * @param array{int,string,string,int,string,int,int,string,string?,string?} $field
	 */
	private function insertField(int $tabId, array $field): void
	{
		[$fieldId, $column, $table, $uitype, $label, $block, $sequence, $typeofdata] = $field;
		$fieldparams = $field[8] ?? '';
		$fieldname = $field[9] ?? $column;
		$this->insert('vtiger_field', [
			'fieldid' => $fieldId,
			'tabid' => $tabId,
			'columnname' => $column,
			'tablename' => $table,
			'generatedtype' => 1,
			'uitype' => $uitype,
			'fieldname' => $fieldname,
			'fieldlabel' => $label,
			'readonly' => 1,
			'presence' => 2,
			'defaultvalue' => '',
			'maximumlength' => 100,
			'sequence' => $sequence,
			'block' => $block,
			'displaytype' => in_array($column, ['createdtime', 'modifiedtime', 'smcreatorid'], true) ? 2 : 1,
			'typeofdata' => $typeofdata,
			'quickcreate' => 1,
			'quickcreatesequence' => 0,
			'info_type' => 'BAS',
			'masseditable' => 1,
			'helpinfo' => '',
			'summaryfield' => 0,
			'fieldparams' => $fieldparams,
			'header_field' => null,
			'maxlengthtext' => 0,
			'maxwidthcolumn' => 0,
		]);
		$this->insert('vtiger_def_org_field', ['tabid' => $tabId, 'fieldid' => $fieldId, 'visible' => 0, 'readonly' => 0]);
	}

	private function cloneProfilesFromEmailTemplates(int $targetTabId): void
	{
		$emailTabId = self::TAB_EMAIL_TEMPLATES;
		$profiles = (new Query())->from('vtiger_profile2tab')->where(['tabid' => $emailTabId])->all($this->db);
		foreach ($profiles as $row) {
			if ((new Query())->from('vtiger_profile2tab')->where(['profileid' => $row['profileid'], 'tabid' => $targetTabId])->exists($this->db)) {
				continue;
			}
			$this->insert('vtiger_profile2tab', [
				'profileid' => $row['profileid'],
				'tabid' => $targetTabId,
				'permissions' => $row['permissions'],
			]);
		}
		$stdRows = (new Query())->from('vtiger_profile2standardpermissions')->where(['tabid' => $emailTabId])->all($this->db);
		foreach ($stdRows as $row) {
			if ((new Query())->from('vtiger_profile2standardpermissions')->where([
				'profileid' => $row['profileid'],
				'tabid' => $targetTabId,
				'operation' => $row['operation'],
			])->exists($this->db)) {
				continue;
			}
			$this->insert('vtiger_profile2standardpermissions', [
				'profileid' => $row['profileid'],
				'tabid' => $targetTabId,
				'operation' => $row['operation'],
				'permissions' => $row['permissions'],
			]);
		}
		$fieldRows = (new Query())->from('vtiger_profile2field')->where(['tabid' => $emailTabId])->all($this->db);
		$targetFields = (new Query())->select('fieldid')->from('vtiger_field')->where(['tabid' => $targetTabId])->column($this->db);
		foreach ($fieldRows as $row) {
			foreach ($targetFields as $fieldId) {
				if ((new Query())->from('vtiger_profile2field')->where([
					'profileid' => $row['profileid'],
					'tabid' => $targetTabId,
					'fieldid' => $fieldId,
				])->exists($this->db)) {
					continue;
				}
				$this->insert('vtiger_profile2field', [
					'profileid' => $row['profileid'],
					'tabid' => $targetTabId,
					'fieldid' => $fieldId,
					'visible' => $row['visible'],
					'readonly' => $row['readonly'],
				]);
			}
		}
		if (!(new Query())->from('vtiger_def_org_share')->where(['tabid' => $targetTabId])->exists($this->db)) {
			$share = (new Query())->from('vtiger_def_org_share')->where(['tabid' => $emailTabId])->one($this->db);
			if ($share) {
				unset($share['ruleid']);
				$this->insert('vtiger_def_org_share', [
					'tabid' => $targetTabId,
					'permission' => $share['permission'],
					'editstatus' => $share['editstatus'],
				]);
			}
		}
	}

	private function registerCustomViews(): void
	{
		if (!(new Query())->from('vtiger_customview')->where(['cvid' => self::CV_DOCUMENT_TEMPLATES])->exists($this->db)) {
			$this->insert('vtiger_customview', [
				'cvid' => self::CV_DOCUMENT_TEMPLATES,
				'viewname' => 'All',
				'setdefault' => 1,
				'setmetrics' => 0,
				'entitytype' => 'DocumentTemplates',
				'status' => 0,
				'userid' => 1,
				'privileges' => 1,
			]);
			$cols = [
				'u_yf_documenttemplates:primary_name:primary_name:DocumentTemplates_LBL_PRIMARY_NAME:V',
				'u_yf_documenttemplates:module_name:module_name:DocumentTemplates_Module:V',
				'u_yf_documenttemplates:status:status:DocumentTemplates_LBL_STATUS:V',
				'vtiger_crmentity:smownerid:assigned_user_id:DocumentTemplates_Assigned_To:V',
			];
			foreach ($cols as $i => $col) {
				$this->insert('vtiger_cvcolumnlist', ['cvid' => self::CV_DOCUMENT_TEMPLATES, 'columnindex' => $i, 'columnname' => $col]);
			}
		}
		if (!(new Query())->from('vtiger_customview')->where(['cvid' => self::CV_TEMPLATE_ELEMENTS])->exists($this->db)) {
			$this->insert('vtiger_customview', [
				'cvid' => self::CV_TEMPLATE_ELEMENTS,
				'viewname' => 'All',
				'setdefault' => 1,
				'setmetrics' => 0,
				'entitytype' => 'TemplateElements',
				'status' => 0,
				'userid' => 1,
				'privileges' => 1,
			]);
			$cols = [
				'u_yf_templateelements:code:code:TemplateElements_LBL_CODE:V',
				'u_yf_templateelements:label:label:TemplateElements_LBL_LABEL:V',
				'u_yf_templateelements:type:type:TemplateElements_LBL_TYPE:V',
				'u_yf_templateelements:module_name:module_name:TemplateElements_Module:V',
				'u_yf_templateelements:status:status:TemplateElements_LBL_STATUS:V',
			];
			foreach ($cols as $i => $col) {
				$this->insert('vtiger_cvcolumnlist', ['cvid' => self::CV_TEMPLATE_ELEMENTS, 'columnindex' => $i, 'columnname' => $col]);
			}
		}
	}

	private function removeSettingsMenuEntries(): void
	{
		$this->delete('vtiger_settings_field', ['fieldid' => [35, 107]]);
	}

	private function migrateWorkflowPdfTemplateProperty(): void
	{
		$rows = (new Query())
			->select(['task_id', 'task'])
			->from('com_vtiger_workflowtasks')
			->where(['like', 'task', '%pdfTemplate%'])
			->all($this->db);
		foreach ($rows as $row) {
			$task = @unserialize($row['task'], ['allowed_classes' => true]);
			if (!is_object($task)) {
				continue;
			}
			if (!empty($task->pdfTemplate) && empty($task->documentTemplate)) {
				$task->documentTemplate = $task->pdfTemplate;
			}
			unset($task->pdfTemplate);
			$this->update('com_vtiger_workflowtasks', ['task' => serialize($task)], ['task_id' => $row['task_id']]);
		}
	}

	private function dropLegacyPdfTables(): void
	{
		if ($this->db->getTableSchema('a_yf_pdf_dynamic_elements', true) !== null) {
			$this->dropTable('a_yf_pdf_dynamic_elements');
		}
		if ($this->db->getTableSchema('a_yf_pdf', true) !== null) {
			$this->dropTable('a_yf_pdf');
		}
	}

	private function refreshTabdata(): void
	{
		\vtlib\Deprecated::createModuleMetaFile();
	}
}
