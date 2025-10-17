<?php

namespace App\Modules\Vtiger\Models;

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */

/**
 * Base Model Class for PSR-4 Migration
 * 
 * This class serves as a base for all model classes in the PSR-4 structure.
 * It extends the runtime base model to provide backward compatibility.
 */
class Model extends \App\Runtime\Vtiger_Base_Model
{
	// This class intentionally empty - serves as namespace bridge
	// All functionality inherited from Vtiger_Base_Model
}

