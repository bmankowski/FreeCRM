<?php

namespace FreeCRM\Modules\Users\Actions;

class ExportData extends \FreeCRM\Runtime\Vtiger_Action_Controller
{

	public function checkPermission(\FreeCRM\Http\Vtiger_Request $request)
	{
		$currentUserModel = \FreeCRM\Modules\Users\Models\Record::getCurrentUserModel();
		if (!$currentUserModel->isAdminUser()) {
			throw new \Exception\NoPermitted('LBL_PERMISSION_DENIED');
		}
	}

	/**
	 * Function exports the data based on the mode
	 * @param Vtiger_Request $request
	 */
	public function ExportData(\FreeCRM\Http\Vtiger_Request $request)
	{
		$adb = \FreeCRM\database\PearDatabase::getInstance();
		$moduleName = $request->get('source_module');

		$this->moduleInstance = \FreeCRM\Modules\Vtiger\Models\Module::getInstance($moduleName);
		$this->moduleFieldInstances = $this->moduleInstance->getFields();
		$this->focus = $this->moduleInstance->getEntityInstance();
		$query = $this->getExportQuery($request);
		$entries = $query->all();

		$headers = ['User Name', 'Title', 'First Name', 'Last Name', 'Email', 'Other Email', 'Secondary Email', 'Office Phone', 'Mobile', 'Fax', 'Street', 'City', 'State', 'Country', 'Postal Code'];
		foreach ($headers as &$header) {
			$translatedHeaders[] = \FreeCRM\Runtime\Vtiger_Language_Handler::translate(html_entity_decode($header, ENT_QUOTES), $moduleName);
		}
		$this->output($request, $translatedHeaders, $entries);
	}

	/**
	 * Function that generates Export Query based on the mode
	 * @param Vtiger_Request $request
	 * @return string export query
	 */
	public function getExportQuery(\FreeCRM\Http\Vtiger_Request $request)
	{
		$cvId = $request->get('viewname');
		$queryGenerator = new \App\QueryGenerator($request->get('source_module'));
		if (!empty($cvId)) {
			$queryGenerator->initForCustomViewById($cvId);
		}
		$acceptedFields = ['user_name', 'title', 'first_name', 'last_name', 'email1', 'email2', 'secondaryemail', 'phone_work', 'phone_mobile', 'phone_fax', 'address_street', 'address_city', 'address_state', 'address_country', 'address_postalcode'];
		$queryGenerator->setFields($acceptedFields);
		return $queryGenerator->createQuery();
	}
}
