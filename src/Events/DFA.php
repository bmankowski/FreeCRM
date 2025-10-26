<?php
/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */
// $ANTLR 3.1 VTEventConditionParser.g 2009-01-23 20:13:11

namespace App\Events;

class DFA
{
    public static function unpackEncodedString($encodedString)
    {
        // Simple implementation for ANTLR 3 compatibility
        // This is a basic unpacking of the encoded string format
        $result = [];
        $len = strlen($encodedString);
        for ($i = 0; $i < $len; $i++) {
            $result[] = ord($encodedString[$i]);
        }
        return $result;
    }
}