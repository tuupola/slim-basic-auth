<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;
use Rector\Php71\Rector\FuncCall\CountOnNullRector;
use Rector\PHPUnit\Set\PHPUnitSetList;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([
        __DIR__ . "/src",
        __DIR__ . "/tests",
    ]);

    $rectorConfig->skip([
        CountOnNullRector::class => [
            __DIR__ . "/tests/BasicAuthenticationTest.php",
        ],
    ]);

    $rectorConfig->sets([
        LevelSetList::UP_TO_PHP_72,
        // SetList::CODE_QUALITY,
        // SetList::DEAD_CODE,
        // SetList::PRIVATIZATION,
        // SetList::NAMING,
        // SetList::TYPE_DECLARATION,
        // SetList::EARLY_RETURN,
        // SetList::TYPE_DECLARATION_STRICT,
        // PHPUnitSetList::PHPUNIT_CODE_QUALITY,
        // PHPUnitSetList::PHPUNIT_90,
        // SetList::CODING_STYLE,
    ]);
};