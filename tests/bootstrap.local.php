<?php

declare(strict_types=1);

$monorepoRoot = dirname(__DIR__, 3);
$autoload = $monorepoRoot . '/vendor/autoload.php';
if (!is_file($autoload)) {
    fwrite(STDERR, "Monorepo autoload not found at {$autoload}\n");
    exit(1);
}

$loader = require $autoload;
$loader->addPsr4('Symfinity\\UiDesigner\\', dirname(__DIR__) . '/src/');
$loader->addPsr4('Symfinity\\UiDesigner\\Tests\\', __DIR__ . '/');
