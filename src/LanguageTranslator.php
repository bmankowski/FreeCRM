<?php
/* +**********************************************************************************
 * The contents of this file are subject to the App Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: App Open Source
 * The Initial Developer of the Original Code is App.
 * Portions created by App are Copyright (C) App.
 * All Rights Reserved.
 * @author Bartłomiej Mańkowski <bmankowski@itconnect.pl>
 * ********************************************************************************** */

/**
 * LanguageTranslator class for secure translation handling
 * Provides Smarty modifier 't' for template translations
 */

namespace App;

use App\Runtime\Vtiger_Language_Handler;
use Exception;

class LanguageTranslator
{
    /**
     * Translate a key to the current language with optional parameters
     * @param string $key Translation key
     * @param mixed ...$args Additional parameters (module name and sprintf parameters)
     * @return string Translated text or original key if not found
     */
    public static function translate(string $key, ...$args)
    {
        // Use the existing Vtiger translation system
        try {
            // First argument after key is module name, rest are sprintf parameters
            $moduleName = $args[0] ?? 'Vtiger';
            $sprintfArgs = array_slice($args, 1);
            
            $formattedString = Vtiger_Language_Handler::getTranslatedString($key, $moduleName);
            
            // If there are sprintf parameters, format the string
            if (!empty($sprintfArgs)) {
                return call_user_func_array('vsprintf', [$formattedString, $sprintfArgs]);
            }
            
            return $formattedString;
        } catch (Exception $exception) {
            // Fallback to original key if translation fails
            return "ERROR:".$key;
        }
    }
}

