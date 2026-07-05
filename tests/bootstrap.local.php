<?php

declare(strict_types=1);

$packageRoot = dirname(__DIR__);

$loader = null;
foreach (spl_autoload_functions() ?: [] as $autoload) {
    if (!\is_array($autoload) || !isset($autoload[0]) || !$autoload[0] instanceof \Composer\Autoload\ClassLoader) {
        continue;
    }

    $loader = $autoload[0];
    break;
}

if (!$loader instanceof \Composer\Autoload\ClassLoader) {
    return;
}

$loader->addPsr4('Symfinity\\UiDesigner\\', $packageRoot.'/src/');
$loader->addPsr4('Symfinity\\UiDesigner\\Tests\\', $packageRoot.'/tests/');
