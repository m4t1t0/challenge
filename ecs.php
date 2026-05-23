<?php

declare(strict_types=1);

use MikelGoig\EasyCodingStandard\SetList as CodingStandard;
use Symplify\EasyCodingStandard\Config\ECSConfig;

return ECSConfig::configure()
    ->withPaths([
        __DIR__ . '/bin',
        __DIR__ . '/config',
        __DIR__ . '/public',
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
    ->withRootFiles()
    ->withSets([CodingStandard::DEFAULT, CodingStandard::RISKY])
    ->withSkip([
        __DIR__ . '/config/',
        __DIR__ . '/migrations/',
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
