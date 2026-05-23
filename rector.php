<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/bin',
        __DIR__ . '/config',
        __DIR__ . '/public',
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
    ->withRootFiles()
    ->withPhpSets()
    ->withAttributesSets()
    ->withComposerBased(doctrine: true, phpunit: true, symfony: true)
    ->withImportNames(importShortClasses: false, removeUnusedImports: true)
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        codingStyle: true,
        typeDeclarations: true,
        privatization: true,
        instanceOf: true,
        earlyReturn: true,
        rectorPreset: true,
        phpunitCodeQuality: true,
        doctrineCodeQuality: true,
        symfonyCodeQuality: true,
        symfonyConfigs: true,
    )
    ->withRules([])
    ->withSkip([
        __DIR__ . '/config/',
        __DIR__ . '/public/',
        __DIR__ . '/tests/Support/_generated',
        __DIR__ . '/tests/bootstrap.php',
        __DIR__ . '/tests/Support/AcceptanceTester.php',
        __DIR__ . '/tests/Support/FunctionalTester.php',
        __DIR__ . '/tests/Support/UnitTester.php',
        __DIR__ . '/src/Kernel.php',
        __DIR__ . '/ecs.php',
        __DIR__ . '/rector.php',
    ])
;
