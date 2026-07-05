<?php

declare(strict_types=1);

namespace Symfinity\UiDesigner\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

final class UiDesignerExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new YamlFileLoader($container, new FileLocator(\dirname(__DIR__, 2) . '/config'));
        $loader->load('services.yaml');

        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        /** @var array<string, mixed> $snapshots */
        $snapshots = $config['snapshots'];
        /** @var array<string, mixed> $undo */
        $undo = $config['undo'];

        $container->setParameter('symfinity.ui_designer.export_root', is_string($config['export_root']) ? $config['export_root'] : '');
        $container->setParameter('symfinity.ui_designer.snapshots.max', is_int($snapshots['max'] ?? null) ? $snapshots['max'] : 10);
        $container->setParameter('symfinity.ui_designer.undo.max_depth', is_int($undo['max_depth'] ?? null) ? $undo['max_depth'] : 20);
        $container->setParameter('symfinity.ui_designer.strict_export', (bool) ($config['strict_export'] ?? false));
        $container->setParameter('symfinity.ui_designer.bundle_path', \dirname(__DIR__, 2));
    }

    public function getAlias(): string
    {
        return 'symfinity_ui_designer';
    }
}
