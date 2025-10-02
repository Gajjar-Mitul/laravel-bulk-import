<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;
use Rector\Transform\Rector\StaticCall\StaticCallToMethodCallRector;
use Rector\ValueObject\PhpVersion;
use RectorLaravel\Rector\ClassMethod\AddGenericReturnTypeToRelationsRector;
use RectorLaravel\Rector\ClassMethod\ScopeNamedClassMethodToScopeAttributedClassMethodRector;
use RectorLaravel\Rector\FuncCall\ArgumentFuncCallToMethodCallRector;
use RectorLaravel\Rector\FuncCall\HelperFuncCallToFacadeClassRector;
use RectorLaravel\Set\LaravelLevelSetList;
use RectorLaravel\Set\LaravelSetList;

return RectorConfig::configure()
    ->withPaths([
        __DIR__.'/app',
        __DIR__.'/config',
        __DIR__.'/resources',
        __DIR__.'/routes',
        __DIR__.'/tests',
    ])
    ->withSets([
        LevelSetList::UP_TO_PHP_84,
        SetList::PHP_POLYFILLS,
        SetList::CODE_QUALITY,
        SetList::CODING_STYLE,
        SetList::DEAD_CODE,
        SetList::STRICT_BOOLEANS,
        SetList::GMAGICK_TO_IMAGICK,
        SetList::PRIVATIZATION,
        SetList::EARLY_RETURN,
        SetList::INSTANCEOF,
        SetList::CARBON,
        LaravelLevelSetList::UP_TO_LARAVEL_120,
        LaravelSetList::ARRAY_STR_FUNCTIONS_TO_STATIC_CALL,
        LaravelSetList::LARAVEL_ARRAYACCESS_TO_METHOD_CALL,
        LaravelSetList::LARAVEL_ARRAY_STR_FUNCTION_TO_STATIC_CALL,
        LaravelSetList::LARAVEL_CODE_QUALITY,
        LaravelSetList::LARAVEL_COLLECTION,
        LaravelSetList::LARAVEL_CONTAINER_STRING_TO_FULLY_QUALIFIED_NAME,
        LaravelSetList::LARAVEL_ELOQUENT_MAGIC_METHOD_TO_QUERY_BUILDER,
        LaravelSetList::LARAVEL_FACADE_ALIASES_TO_FULL_NAMES,
        LaravelSetList::LARAVEL_IF_HELPERS,
        LaravelSetList::LARAVEL_LEGACY_FACTORIES_TO_CLASSES,
        LaravelSetList::LARAVEL_STATIC_TO_INJECTION,
    ])
    ->withRules([
        AddGenericReturnTypeToRelationsRector::class,
    ])
    ->withSkip([
        ArgumentFuncCallToMethodCallRector::class,
        StaticCallToMethodCallRector::class,
        HelperFuncCallToFacadeClassRector::class,
        ScopeNamedClassMethodToScopeAttributedClassMethodRector::class,
        // Skip vendor directory
        __DIR__.'/vendor',
        // Skip generated files
        __DIR__.'/bootstrap/cache',
        __DIR__.'/storage',
    ])
    ->withIndent(indentChar: ' ', indentSize: 4)
    ->withImportNames(removeUnusedImports: true)
    ->withPhpVersion(PhpVersion::PHP_84)
    ->withTypeCoverageLevel(40);
