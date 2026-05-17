<?php

namespace App\Modules\Kandydaci\Models;

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
 * Kandydaci ListView Model Class.
 */
class ListView extends \App\Modules\Base\Models\ListView {

    /**
     * Function to give advance links of a module.
     *
     * @return array of advanced links
     */
    public function getAdvancedLinks() {
		//@var $moduleModel \App\Modules\Kandydaci\Models\Module
        $moduleModel = $this->getModule();

        $advancedLinks = parent::getAdvancedLinks();


		$user = \App\User\CurrentUser::get();

		//If user is not admin, do not show the link
		if($user->isAdminUser()) {
			$advancedLinks[] = [
				'linktype' => 'LISTVIEW',
				'linklabel' => 'LBL_RUN_IMPORT_CANDIDATES',
				'linkdata' => ['url' => $moduleModel->getImportCandidatesURL(), 'type' => 'modal'],
				'linkclass' => 'btn-light js-show-modal',
				'linkicon' => 'fas fa-head-side-virus',
			];
		}


        return $advancedLinks;
    }
}
