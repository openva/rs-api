<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\LevelSetList;

return static function (RectorConfig $rectorConfig): void {
    // 1) Define which directories Rector should process:
    $rectorConfig->paths([
        __DIR__ . '/htdocs',
        __DIR__ . '/deploy',
    ]);

    // 2) Target modern syntax so Rector upgrades toward PHP 8.x instead of downgrading.
    $rectorConfig->sets([
        LevelSetList::UP_TO_PHP_83,
    ]);

    // 3) If you had specific rules previously (like AddVoidReturnTypeWhereNoReturnRector),
    //    remove them or skip them because those rules add modern type hints:
    $rectorConfig->skip([
        // e.g. Rector\TypeDeclaration\Rector\ClassMethod\AddVoidReturnTypeWhereNoReturnRector::class,
    ]);

    // 4) (Optional) Add additional sets for code quality/cleanup if they don't
    //    introduce modern syntax. For example:
    // $rectorConfig->sets([
    //     SetList::DEAD_CODE,  // But be careful that it doesn't introduce PHP 7+ array destructuring, etc.
    // ]);
};
