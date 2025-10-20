<?php

namespace App\Modules\Vtiger\Models;

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

