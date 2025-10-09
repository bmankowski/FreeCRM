<?php
/* +**********************************************************************************
 * The contents of this file are subject to the FreeCRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: FreeCRM Open Source
 * The Initial Developer of the Original Code is FreeCRM.
 * Portions created by FreeCRM are Copyright (C) FreeCRM.
 * All Rights Reserved.
 * @author Bartłomiej Mańkowski <bmankowski@itconnect.pl>
 * ********************************************************************************** */

/**
 * LanguageTranslator class for secure translation handling
 * Provides Smarty modifier 't' for template translations
 */

namespace FreeCRM;

use FreeCRM\Runtime\Vtiger_Language_Handler;
use Exception;

class LanguageTranslator
{
    /**
     * Translate a key to the current language
     * @param string $key Translation key
     * @param string $moduleName Module name (optional, defaults to 'Vtiger')
     * @return string Translated text or original key if not found
     */
    public static function translate(string $key, $moduleName = 'Vtiger')
    {
        // Use the existing Vtiger translation system
        try {
            return Vtiger_Language_Handler::getTranslatedString($key, $moduleName);
        } catch (Exception $exception) {
            // Fallback to original key if translation fails
            return "ERROR:".$key;
        }
    }
}

