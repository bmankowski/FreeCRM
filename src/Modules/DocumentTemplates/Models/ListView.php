<?php

namespace App\Modules\DocumentTemplates\Models;

class ListView extends \App\Modules\Base\Models\ListView
{
	public function loadListViewCondition()
	{
		parent::loadListViewCondition();
		$sourceModule = $this->get('sourceModule');
		if (!empty($sourceModule)) {
			$this->getQueryGenerator()->addCondition('module_name', $sourceModule, 'e');
		}
	}

	public function getBasicLinks()
	{
		$basicLinks = parent::getBasicLinks();
		$sourceModule = $this->get('sourceModule');
		if ($sourceModule && !empty($basicLinks)) {
			foreach ($basicLinks as &$link) {
				if (($link['linklabel'] ?? '') === 'LBL_ADD_RECORD' && !empty($link['linkurl'])) {
					$link['linkurl'] .= '&source_module=' . rawurlencode($sourceModule);
				}
			}
			unset($link);
		}
		return $basicLinks;
	}

	public function getAdvancedLinks()
	{
		$moduleModel = $this->getModule();
		$advancedLinks = parent::getAdvancedLinks();
		if ($moduleModel->isPermitted('EditView')) {
			$advancedLinks[] = [
				'linktype' => 'LISTVIEW',
				'linklabel' => 'LBL_IMPORT_TEMPLATE',
				'linkurl' => 'index.php?module=DocumentTemplates&view=Import',
				'linkicon' => '',
			];
		}
		return $advancedLinks;
	}
}
