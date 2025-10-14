<?php

namespace FreeCRM\Modules\Settings\Companies\Actions;



/**
 * Companies SaveAjax action model class
 * @package YetiForce.Settings.Action
 * @license licenses/License.html
 * @author Adrian Koń <a.kon@yetiforce.com>
 */

use FreeCRM\Modules\Settings\Companies\Models\Record as Settings_Companies_Record_Model;
class SaveAjax extends \FreeCRM\Modules\Settings\Vtiger\Views\IndexAjax
{

	/**
	 * Constructor
	 */
	public function __construct()
	{
		parent::__construct();
		$this->exposeMethod('updateCompany');
	}

	/**
	 * Function to save company info
	 * @param \FreeCRM\Http\Vtiger_Request $request
	 * @return array
	 */
	public function updateCompany(\FreeCRM\Http\Vtiger_Request $request)
	{
		$recordId = $request->get('record');
		if (!empty($recordId)) {
			$recordModel = Settings_Companies_Record_Model::getInstance($recordId);
		} else {
			$recordModel = new Settings_Companies_Record_Model();
		}
		$exists = $recordModel->isCompanyDuplicated($request);
		if (!$exists) {
			$recordModel->setCompaniesNotDefault($request->get('default'));
			$logoDetails = $recordModel->saveCompanyLogos();
			$columns = Settings_Companies_Module_Model::getColumnNames();
			if ($columns) {
				if (empty(($request->get('default')))) {
					$columns = array_diff($columns, ['default']);
				}
				foreach ($columns as $fieldName) {
					$fieldValue = $request->get($fieldName);
					if ($fieldName === 'logo_login' || $fieldName === 'logo_main' || $fieldName === 'logo_mail') {
						if (!empty($logoDetails[$fieldName]['name'])) {
							$fieldValue = ltrim(basename(" " . \App\Fields\File::sanitizeUploadFileName($logoDetails[$fieldName]['name'])));
						} else {
							$fieldValue = $recordModel->get($fieldName);
						}
					}
					if ('default' === $fieldName) {
						$fieldValue = $request->get('default');
					}
					$recordModel->set($fieldName, $fieldValue);
				}
				$recordModel->save();
			}
			$result = ['success' => true, 'url' => $recordModel->getDetailViewUrl()];
		} else {
			$result = ['success' => false, 'message' => \LanguageTranslator::translate('LBL_COMPANY_NAMES_EXIST', $request->getModule(false))];
		}
		$response = new \FreeCRM\Http\Vtiger_Response();
		$response->setResult($result);
		$response->emit();
	}

	/**
	 * Validate Request
	 * @param \FreeCRM\Http\Vtiger_Request $request
	 */
	public function validateRequest(\FreeCRM\Http\Vtiger_Request $request)
	{
		$request->validateWriteAccess();
	}
}
