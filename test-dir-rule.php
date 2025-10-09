<?php

// Test file for __DIR__ replacement rule
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/include/utils/utils.php';

class TestClass
{
    public function testMethod()
    {
        $path = __DIR__ . '/some/file.php';
        return $path;
    }
}


