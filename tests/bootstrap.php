<?php

declare(strict_types=1);

/**
 * PHPUnit bootstrap — single Composer autoload only.
 *
 * Monorepo: project-root vendor (mono qa:test). Split mirror: package vendor only.
 *
 * Optional package hook: tests/bootstrap.local.php (KERNEL_CLASS, Dotenv, …).
 */

$packageRoot = dirname(__DIR__);
$autoload = null;

$dir = $packageRoot;
for ($i = 0; $i < 5; ++$i) {
    if (is_file($dir.'/mono.json') && is_file($dir.'/vendor/autoload.php')) {
        $autoload = $dir.'/vendor/autoload.php';
        break;
    }

    $parent = dirname($dir);
    if ($parent === $dir) {
        break;
    }
    $dir = $parent;
}

if ($autoload === null && is_file($packageRoot.'/vendor/autoload.php')) {
    $autoload = $packageRoot.'/vendor/autoload.php';
}

if ($autoload === null) {
    fwrite(STDERR, "Composer autoload not found — run composer install in the package or monorepo.\n");
    exit(1);
}

if (!class_exists(\Composer\Autoload\ClassLoader::class, false)) {
    require $autoload;
}

$localBootstrap = __DIR__.'/bootstrap.local.php';
if (is_file($localBootstrap)) {
    require $localBootstrap;
}
