<?php
/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */
// $ANTLR 3.1 VTEventConditionParser.g 2009-01-23 20:13:10

namespace App\Events;

class Set
{
    private $data;
    
    public function __construct($data = [])
    {
        $this->data = is_array($data) ? $data : [$data];
    }
    
    public function add($item)
    {
        if (!in_array($item, $this->data)) {
            $this->data[] = $item;
        }
    }
    
    public function contains($item)
    {
        return in_array($item, $this->data);
    }
    
    public function toArray()
    {
        return $this->data;
    }
}              