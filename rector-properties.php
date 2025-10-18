<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Php74\Rector\Property\TypedPropertyRector;
use Rector\TypeDeclaration\Rector\Property\TypedPropertyFromAssignsRector;
use Rector\TypeDeclaration\Rector\ClassMethod\AddVoidReturnTypeWhereNoReturnRector;
use Rector\Privatization\Rector\Property\PrivatizeFinalClassPropertyRector;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/src/events',
    ])
    ->withRules([
        // This rule adds property declarations based on assignments in constructor and other methods
        TypedPropertyFromAssignsRector::class,
    ])
    ->withSkip([
        // Skip auto-generated parser files
        __DIR__ . '/src/events/VTEventConditionParserLexer.php',
        __DIR__ . '/src/events/VTEventConditionParserParser.php',
        __DIR__ . '/vendor',
    ])
    // Import names (use statements) for better type hints
    ->withImportNames();

