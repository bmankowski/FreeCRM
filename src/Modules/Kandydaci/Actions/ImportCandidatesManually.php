<?php

namespace App\Modules\Kandydaci\Actions;

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
class ImportCandidatesManually extends \App\Modules\Base\Actions\Save {

	public \App\Modules\Documents\Models\Record $document;
	public \App\Modules\Kandydaci\Models\Record $candidate;

	/** {@inheritdoc} */
	public function checkPermission(\App\Http\Vtiger_Request $request) {

	}

	/** Converting document to CV and loading it as an MultiAttachment
	 * After adding document as a CV refresh Candidate's page */
	public function process(\App\Http\Vtiger_Request $request) {

		(new \App\Modules\RecruitmentApplication\Services\RecruitmentApplicationImporter())->importPending();

		$moduleName = $request->getModule();
		$module = \App\Modules\Base\Models\Module::getInstance($moduleName);
		$url = $module->getListViewUrl();
		$response = new \App\Http\Vtiger_Response();
		$response->setResult(['redirect' => $url]);
		$response->emit();
	}
}
