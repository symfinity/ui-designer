<?php

declare(strict_types=1);

namespace Symfinity\UiDesigner\Tests\Unit\Typography;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfinity\UiDesigner\Typography\FonttrioPairingCatalog;

final class FonttrioPairingCatalogTest extends TestCase
{
    #[Test]
    public function bundledCatalogListsFonttrioPairings(): void
    {
        $catalogPath = dirname(__DIR__, 3) . '/config/font_pairing_catalog.yaml';
        $catalog = new FonttrioPairingCatalog($catalogPath);

        $entries = $catalog->list();

        self::assertGreaterThanOrEqual(20, count($entries));

        $editorial = null;
        foreach ($entries as $entry) {
            if ($entry['id'] === 'editorial') {
                $editorial = $entry;
                break;
            }
        }

        self::assertNotNull($editorial);
        self::assertStringContainsString('Playfair', $editorial['label']);
        self::assertSame('@fonttrio/editorial', $editorial['source']);
    }
}
