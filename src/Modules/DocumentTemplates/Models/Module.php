<?php

namespace App\Modules\DocumentTemplates\Models;

class Module extends \App\Modules\Base\Models\Module
{
	public $name = 'DocumentTemplates';
	public $baseTable = 'u_yf_documenttemplates';
	public $baseIndex = 'documenttemplatesid';
	public $listFields = [
		'module_name' => 'LBL_MAIN_MODULE',
		'primary_name' => 'LBL_PRIMARY_NAME',
		'secondary_name' => 'LBL_SECONDARY_NAME',
		'status' => 'LBL_STATUS',
		'default' => 'LBL_DEFAULT_TPL',
	];
	public $nameFields = ['primary_name'];

	public static $allFields = [
		'module_name', 'status', 'primary_name', 'secondary_name', 'meta_author', 'meta_creator',
		'meta_keywords', 'metatags_status', 'meta_subject', 'meta_title', 'page_format', 'margin_chkbox',
		'margin_top', 'margin_bottom', 'margin_left', 'margin_right', 'header_height', 'footer_height',
		'page_orientation', 'language', 'filename', 'visibility', 'default', 'header_content', 'body_content',
		'footer_content', 'conditions', 'watermark_type', 'watermark_text', 'watermark_size', 'watermark_angle',
		'template_members', 'watermark_image', 'one_file',
	];

	public static $step1Fields = ['status', 'primary_name', 'secondary_name', 'module_name', 'metatags_status', 'meta_subject', 'meta_title', 'meta_author', 'meta_creator', 'meta_keywords'];
	public static $step2Fields = ['page_format', 'margin_chkbox', 'margin_top', 'margin_bottom', 'margin_left', 'margin_right', 'header_height', 'footer_height', 'page_orientation', 'language', 'filename', 'visibility', 'default', 'one_file'];
	public static $step3Fields = ['module_name', 'header_content', 'body_content', 'footer_content'];
	public static $step4Fields = ['conditions'];
	public static $step5Fields = ['template_members'];
	public static $step6Fields = ['watermark_type', 'watermark_text', 'watermark_size', 'watermark_angle', 'watermark_image'];

	public static $uploadPath = 'storage/DocumentTemplates/watermark/';

	public static function getFieldsByStep($step = 1)
	{
		switch ((int) $step) {
			case 6:
				return self::$step6Fields;
			case 5:
				return self::$step5Fields;
			case 4:
				return self::$step4Fields;
			case 3:
				return self::$step3Fields;
			case 2:
				return self::$step2Fields;
			case 1:
			default:
				return self::$step1Fields;
		}
	}

	public function getListFields(): array
	{
		return parent::getListFields();
	}

	public function hasCreatePermissions(): bool
	{
		return \App\Modules\Users\Models\Privileges::isPermitted('DocumentTemplates', 'EditView');
	}

	public function isPagingSupported(): bool
	{
		return true;
	}

	public static function checkRequestPermission(\App\Http\Vtiger_Request $request, string $permission = 'EditView'): void
	{
		$moduleModel = \App\Modules\Base\Models\Module::getInstance('DocumentTemplates');
		if (!$moduleModel || !\App\Modules\Users\Models\Privileges::isPermitted('DocumentTemplates', $permission)) {
			throw new \App\Exceptions\NoPermitted('LBL_PERMISSION_DENIED');
		}
	}

	public function getDefaultUrl()
	{
		return 'index.php?module=DocumentTemplates&view=ListView';
	}

	public function getCreateRecordUrl()
	{
		return 'index.php?module=DocumentTemplates&view=Edit';
	}

	public static function getSupportedModules()
	{
		$moduleModels = \App\Modules\Base\Models\Module::getAll([0, 2]);
		$supportedModuleModels = [];
		foreach ($moduleModels as $tabId => $moduleModel) {
			if ($moduleModel->isEntityModule()) {
				$supportedModuleModels[$tabId] = $moduleModel;
			}
		}
		return $supportedModuleModels;
	}

	public static function getPageFormats()
	{
		return [
			'4A0', '2A0',
			'A0', 'A1', 'A2', 'A3', 'A4', 'A5', 'A6', 'A7', 'A8', 'A9', 'A10',
			'B0', 'B1', 'B2', 'B3', 'B4', 'B5', 'B6', 'B7', 'B8', 'B9', 'B10',
			'C0', 'C1', 'C2', 'C3', 'C4', 'C5', 'C6', 'C7', 'C8', 'C9', 'C10',
			'RA0', 'RA1', 'RA2', 'RA3', 'RA4',
			'SRA0', 'SRA1', 'SRA2', 'SRA3', 'SRA4',
			'LETTER', 'LEGAL', 'LEDGER', 'TABLOID', 'EXECUTIVE', 'FOLIO',
			'B', 'A', 'DEMY', 'ROYAL',
		];
	}

	public function getTemplatesByModule($moduleName)
	{
		return \App\Modules\Base\Models\DocumentTemplate::getTemplatesByModule($moduleName);
	}
}
