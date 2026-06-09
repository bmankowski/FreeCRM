<?php

namespace App\Modules\Candidates\Models;

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Contributor(s): YetiForce S.A.
 * *********************************************************************************** */

/**
 * Vtiger Module Model Class.
 */
class Module extends \App\Modules\Base\Models\Module {

	public static function getCVPathname(): string {
//		$documentRecordModel = \App\Modules\Documents\Models\Record::getInstanceById(1325274,"Documents");
		//Download the file
//		return $documentRecordModel->getDownloadFileURL();
		return "cv-test.pdf";

	}

	public function getImportCandidatesURL() : string {
		return 'index.php?module=' . $this->getName() . '&view=ImportCandidatesModal&fromview=List';
	}
}
