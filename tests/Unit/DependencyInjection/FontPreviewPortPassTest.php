<?php

declare(strict_types=1);

namespace Symfinity\UiDesigner\Tests\Unit\DependencyInjection;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfinity\UiDesigner\DependencyInjection\Compiler\FontPreviewPortPass;
use Symfinity\UiDesigner\Typography\FontManagerFontPreviewAdapter;
use Symfinity\UiDesigner\Typography\FontPreviewPort;
use Symfinity\UiDesigner\Typography\NullFontPreviewPort;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

final class FontPreviewPortPassTest extends TestCase
{
    #[Test]
    public function wiresNullPortWhenFontManagerAbsent(): void
    {
        $container = new ContainerBuilder();
        $container->register(NullFontPreviewPort::class, NullFontPreviewPort::class);

        (new FontPreviewPortPass())->process($container);

        self::assertTrue($container->hasAlias(FontPreviewPort::class));
        self::assertSame(NullFontPreviewPort::class, (string) $container->getAlias(FontPreviewPort::class));
    }

    #[Test]
    public function wiresFontManagerAdapterWhenDependenciesPresent(): void
    {
        $container = new ContainerBuilder();
        $container->register(NullFontPreviewPort::class, NullFontPreviewPort::class);
        $container->setParameter('font_manager.pairings', ['active' => 'editorial']);
        $container->setParameter('font_manager.fonts', ['body' => ['family' => 'Inter']]);
        $container->register('Symfinity\\FontManager\\Provider\\ProviderRegistry');
        $container->setAlias('Symfinity\\FontManager\\Import\\FontPairingImportPort', 'Symfinity\\FontManager\\Import\\FonttrioPairingAdapter');
        $container->register('Symfinity\\FontManager\\Import\\FonttrioPairingAdapter');
        $container->setDefinition(FontManagerFontPreviewAdapter::class, new Definition(FontManagerFontPreviewAdapter::class));

        (new FontPreviewPortPass())->process($container);

        self::assertTrue($container->hasDefinition(FontManagerFontPreviewAdapter::class));
        self::assertSame(FontManagerFontPreviewAdapter::class, (string) $container->getAlias(FontPreviewPort::class));
    }
}
