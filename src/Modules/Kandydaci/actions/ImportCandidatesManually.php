<?php

/**
 * Create outsource offers action file.
 * Tworzenie oferty Mass Outsource na podstawie danych pobranych z Konsultanta, Kontaktów i Cenników
 * @package   Action
 *
 * @copyright YetiForce Sp. z o.o
 * @license   YetiForce Public License 4.0 (licenses/LicenseEN.txt or yetiforce.com)
 * @author    Bartłomiej Mańkowski <bmankowski@itconnect.pl>
 */

/**
 * Create outsource offers action class.
 */
class Kandydaci_ImportCandidatesManually_Action extends Vtiger_Save_Action {

	public Documents_Record_Model $document;
	public Kandydaci_Record_Model $candidate;

	/** {@inheritdoc} */
	public function checkPermission(App\Request $request) {

	}

	/** Converting document to CV and loading it as an MultiAttachment
	 * After adding document as a CV refresh Candidate's page */
	public function process(App\Request $request) {

		Kandydaci_ScheduledImport_Cron::importNewCandidates();

		$moduleName = $request->getModule();
		$module = Vtiger_Module_Model::getInstance($moduleName);
		$url = $module->getListViewUrl();
		$response = new Vtiger_Response();
		$response->setResult(['redirect' => $url]);
		$response->emit();
	}
}
