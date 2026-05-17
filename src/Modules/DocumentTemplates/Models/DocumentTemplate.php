<?php
/**
 * FreeCRM - Document template runtime model.
 */

declare(strict_types=1);

namespace App\Modules\DocumentTemplates\Models;

class DocumentTemplate extends \App\Modules\Base\Models\PDF
{
	public static $baseTable = 'u_yf_documenttemplates';
	public static $baseIndex = 'documenttemplatesid';

	public function getId()
	{
		return $this->get('documenttemplatesid');
	}

	public function getName()
	{
		$name = (string) $this->get('primary_name');
		return $name !== '' ? $name : (string) $this->get('filename');
	}

	public function deleteConditions(): void
	{
		$this->set('conditions', '[]');
	}

	/**
	 * Load template by id (always DocumentTemplates model, not CRM module handler).
	 */
	public static function getInstanceById($recordId, $moduleName = 'Vtiger')
	{
		$pdf = \App\Cache\Cache::get('DocumentTemplateModel', $recordId);
		if ($pdf instanceof self) {
			return $pdf;
		}
		$row = (new \App\Db\Query())->from(self::$baseTable)->where([self::$baseIndex => $recordId])->one();
		if ($row === false) {
			return false;
		}
		$pdf = new self();
		$pdf->setData($row);
		\App\Cache\Cache::save('DocumentTemplateModel', $recordId, $pdf);
		return $pdf;
	}
}
