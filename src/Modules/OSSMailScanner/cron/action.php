<?php

namespace App\Modules\OSSMailScanner\cron;
/* +***********************************************************************************************************************************
 * The contents of this file are subject to the YetiForce Public License Version 1.1 (the "License"); you may not use this file except
 * in compliance with the License.
 * Software distributed under the License is distributed on an "AS IS" basis, WITHOUT WARRANTY OF ANY KIND, either express or implied.
 * See the License for the specific language governing rights and limitations under the License.
 * The Original Code is YetiForce.
 * The Initial Developer of the Original Code is YetiForce. Portions created by YetiForce are Copyright (C) www.yetiforce.com. 
 * All Rights Reserved.
 * *********************************************************************************************************************************** */
$recordModel = \App\Modules\Base\Models\Record::getCleanInstance('OSSMailScanner');
$user_name = '';
if (PHP_SAPI == 'cgi-fcgi') {
	$user_name = \App\Modules\Users\Models\Record::getCurrentUserModel()->user_name;
}
$recordModel->executeCron(PHP_SAPI . ' - ' . $user_name);
