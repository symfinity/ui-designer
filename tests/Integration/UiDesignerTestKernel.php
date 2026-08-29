<?php

declare(strict_types=1);

namespace Symfinity\UiDesigner\Tests\Integration;

use Symfinity\UiDesigner\Tests\Functional\Controller\HostPreviewFixtureController;
use Symfinity\UiDesigner\UiDesignerBundle;
use Symfinity\UiKernel\UiKernelBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

class UiDesignerTestKernel extends Kernel
{
    use MicroKernelTrait;

    public function registerBundles(): array
    {
        return [
            new FrameworkBundle(),
            new TwigBundle(),
            new UiKernelBundle(),
            new UiDesignerBundle(),
        ];
    }

    protected function configureRoutes(RoutingConfigurator $routes): void
    {
        $routes->import(\dirname(__DIR__, 2) . '/config/routes/ui_designer.yaml');
        $routes->import(\dirname(__DIR__) . '/Functional/Controller/HostPreviewFixtureController.php', 'attribute');
    }

    protected function configureContainer(ContainerConfigurator $container): void
    {
        $container->extension('framework', [
            'secret' => 'test-secret',
            'test' => true,
            'csrf_protection' => true,
            'translator' => ['enabled' => false],
            'router' => ['utf8' => true],
            'php_errors' => ['log' => false],
            'form' => ['enabled' => false],
            'fragments' => ['path' => '/_fragment'],
            'session' => [
                'storage_factory_id' => 'session.storage.factory.mock_file',
            ],
        ]);
        $container->extension('symfinity_ui_designer', [
            'export_root' => sys_get_temp_dir() . '/ui-designer-test-' . getmypid(),
            'snapshots' => ['max' => 10],
            'undo' => ['max_depth' => 20],
            'strict_export' => false,
        ]);

        $container->services()
            ->set(HostPreviewFixtureController::class)
            ->public()
            ->autowire()
            ->autoconfigure();
    }
}
