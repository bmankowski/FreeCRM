<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * Thin wrapper exposing translation helpers under the App\Language
 * namespace so that templates can call \App\Language::translate().
 */

declare(strict_types=1);

namespace App;

use App\Runtime\Vtiger_Language_Handler;

class Language extends Vtiger_Language_Handler
{
}

