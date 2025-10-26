<?php

namespace App\Modules\Settings\Companies\Actions;



/**
 * Companies SaveAjax action model class
 * @package YetiForce.Settings.Action
 * @license licenses/License.html
 * @author Adrian Koń <a.kon@yetiforce.com>
 */

class SaveAjax extends \App\Modules\Settings\Base\Views\IndexAjax
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
	 * @param \App\Http\Vtiger_Request $request
	 * @return array
	 */
	public function updateCompany(\App\Http\Vtiger_Request $request)
	{
		$recordId = $request->get('record');
		if (!empty($recordId)) {
			$recordModel = \App\Modules\Settings\Companies\Models\Record::getInstance($recordId);
		} else {
			$recordModel = new \App\Modules\Settings\Companies\Models\Record();
		}
		$exists = $recordModel->isCompanyDuplicated($request);
		if (!$exists) {
			$recordModel->setCompaniesNotDefault($request->get('default'));
			$logoDetails = $recordModel->saveCompanyLogos();
			$columns = \App\Modules\Settings\Companies\Models\Module::getColumnNames();
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
			$result = ['success' => false, 'message' => \App\Runtime\Vtiger_Language_Handler::translate('LBL_COMPANY_NAMES_EXIST', $request->getModule(false))];
		}
		$response = new \App\Http\Vtiger_Response();
		$response->setResult($result);
		$response->emit();
	}

	/**
	 * Validate Request
	 * @param \App\Http\Vtiger_Request $request
	 */
	public function validateRequest(\App\Http\Vtiger_Request $request)
	{
		$request->validateWriteAccess();
	}
}
