<?php

declare(strict_types=1);

namespace Symfinity\UiDesigner\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('symfinity_ui_designer');

        /** @var \Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition $root */
        $root = $treeBuilder->getRootNode();

        $root
            ->children()
                ->scalarNode('export_root')
                    ->defaultValue('%kernel.project_dir%/var/ui-designer')
                    ->cannotBeEmpty()
                ->end()
                ->arrayNode('snapshots')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->integerNode('max')->defaultValue(10)->min(1)->max(100)->end()
                    ->end()
                ->end()
                ->arrayNode('undo')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->integerNode('max_depth')->defaultValue(20)->min(1)->max(100)->end()
                    ->end()
                ->end()
                ->booleanNode('strict_export')->defaultFalse()->end()
            ->end();

        return $treeBuilder;
    }
}
