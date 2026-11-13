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

$loader = require $autoload;
if ($loader instanceof Composer\Autoload\ClassLoader && is_file($packageRoot.'/composer.json')) {
    $composer = json_decode((string) file_get_contents($packageRoot.'/composer.json'), true);
    if (is_array($composer)) {
        foreach (['autoload', 'autoload-dev'] as $section) {
            foreach ($composer[$section]['psr-4'] ?? [] as $prefix => $relativePaths) {
                $paths = is_array($relativePaths) ? $relativePaths : [$relativePaths];
                foreach ($paths as $relativePath) {
                    if (!is_string($prefix) || !is_string($relativePath)) {
                        continue;
                    }
                    $loader->addPsr4($prefix, $packageRoot.'/'.trim($relativePath, '/').'/');
                }
            }
        }
    }
}

$localBootstrap = __DIR__.'/bootstrap.local.php';
if (is_file($localBootstrap)) {
    require $localBootstrap;
}
