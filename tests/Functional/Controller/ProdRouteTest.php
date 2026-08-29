<?php

declare(strict_types=1);

namespace Symfinity\UiDesigner\Tests\Functional\Controller;

use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfinity\UiDesigner\Tests\Integration\UiDesignerTestKernel;

final class ProdRouteTest extends WebTestCase
{
    protected static function getKernelClass(): string
    {
        return UiDesignerTestKernel::class;
    }

    protected static function createKernel(array $options = []): UiDesignerTestKernel
    {
        return new UiDesignerTestKernel('prod', false);
    }

    #[Test]
    public function studioRoutesNotRegisteredInProd(): void
    {
        $client = static::createClient();
        $client->request('GET', '/ui-designer/studio');
        self::assertResponseStatusCodeSame(404);
    }
}
