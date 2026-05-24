<?php

declare(strict_types=1);

use PhpCsFixer\Fixer\PhpUnit\PhpUnitMethodCasingFixer;
use PhpCsFixer\Fixer\Strict\DeclareStrictTypesFixer;
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
    ->withPhpCsFixerSets(
        symfony: true,
        symfonyRisky: true,
    )
    // Project overrides on @Symfony:risky defaults:
    //   - keep declare(strict_types=1) in every file (Symfony omits them; we want them).
    //   - keep snake_case test method names (Symfony uses camelCase; ours read as specs).
    ->withConfiguredRule(DeclareStrictTypesFixer::class, ['strategy' => 'enforce'])
    ->withConfiguredRule(PhpUnitMethodCasingFixer::class, ['case' => 'snake_case'])
    ->withSkip([
        __DIR__ . '/config/',
        __DIR__ . '/public/',
        __DIR__ . '/tests/bootstrap.php',
        __DIR__ . '/src/Kernel.php',
        __DIR__ . '/ecs.php',
        __DIR__ . '/rector.php',
    ])
;
