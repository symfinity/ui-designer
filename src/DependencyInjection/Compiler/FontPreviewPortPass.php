<?php

declare(strict_types=1);

namespace Symfinity\UiDesigner\DependencyInjection\Compiler;

use Symfinity\UiDesigner\Typography\FontManagerFontPreviewAdapter;
use Symfinity\UiDesigner\Typography\FontPreviewPort;
use Symfinity\UiDesigner\Typography\FonttrioPairingCatalog;
use Symfinity\UiDesigner\Typography\NullFontPreviewPort;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Wires font-manager after all bundle extensions have registered services/parameters.
 */
final class FontPreviewPortPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition(NullFontPreviewPort::class)) {
            $container->register(NullFontPreviewPort::class, NullFontPreviewPort::class)
                ->setAutowired(false)
                ->setAutoconfigured(false);
        }

        if ($this->canUseFontManager($container)) {
            if ($container->hasDefinition(FontManagerFontPreviewAdapter::class)) {
                $container->removeDefinition(FontManagerFontPreviewAdapter::class);
            }

            if (!$container->hasDefinition(FonttrioPairingCatalog::class)) {
                $container->register(FonttrioPairingCatalog::class, FonttrioPairingCatalog::class)
                    ->setAutowired(false)
                    ->setAutoconfigured(false)
                    ->setArgument('$catalogPath', '%symfinity.ui_designer.bundle_path%/config/font_pairing_catalog.yaml');
            }

            $container->register(FontManagerFontPreviewAdapter::class, FontManagerFontPreviewAdapter::class)
                ->setAutowired(false)
                ->setAutoconfigured(false)
                ->setArgument('$providerRegistry', new Reference('Symfinity\\FontManager\\Provider\\ProviderRegistry'))
                ->setArgument('$pairingImportPort', new Reference('Symfinity\\FontManager\\Import\\FontPairingImportPort'))
                ->setArgument('$bundledPairingCatalog', new Reference(FonttrioPairingCatalog::class))
                ->setArgument('$pairingsConfig', '%font_manager.pairings%')
                ->setArgument('$fontsConfig', '%font_manager.fonts%');
            $container->setAlias(FontPreviewPort::class, FontManagerFontPreviewAdapter::class);

            return;
        }

        $container->setAlias(FontPreviewPort::class, NullFontPreviewPort::class);
    }

    private function canUseFontManager(ContainerBuilder $container): bool
    {
        return $container->hasParameter('font_manager.pairings')
            && $container->hasParameter('font_manager.fonts')
            && $container->has('Symfinity\\FontManager\\Provider\\ProviderRegistry')
            && $container->has('Symfinity\\FontManager\\Import\\FontPairingImportPort');
    }
}
