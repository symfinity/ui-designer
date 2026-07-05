<?php

declare(strict_types=1);

namespace Symfinity\UiDesigner;

use Symfinity\UiDesigner\DependencyInjection\UiDesignerExtension;
use Symfony\Bundle\FrameworkBundle\DependencyInjection\Configurator\TwigConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

final class UiDesignerBundle extends Bundle
{
    public function getContainerExtension(): ExtensionInterface
    {
        return new UiDesignerExtension();
    }

    public function getPath(): string
    {
        return \dirname(__DIR__);
    }

    public function configureRoutes(RoutingConfigurator $routes): void
    {
        $routes->import($this->getPath() . '/config/routes/ui_designer.yaml');
    }

    public function configureTwig(TwigConfigurator $configurator): void
    {
        $configurator->path($this->getPath() . '/templates', 'UiDesigner');
    }
}
