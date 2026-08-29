<?php

declare(strict_types=1);

namespace Symfinity\UiDesigner\Tests\Unit\Typography;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfinity\FontManager\Import\FontPairingImportPort;
use Symfinity\FontManager\Provider\ProviderRegistry;
use Symfinity\UiDesigner\Typography\FontManagerFontPreviewAdapter;
use Symfinity\UiDesigner\Typography\FonttrioPairingCatalog;

final class FontManagerListPairingsTest extends TestCase
{
    protected function setUp(): void
    {
        if (!class_exists(ProviderRegistry::class)) {
            self::markTestSkipped('symfinity/font-manager is not installed.');
        }
    }

    #[Test]
    public function mergesBundledCatalogWithConsumerOverrides(): void
    {
        $catalogPath = dirname(__DIR__, 3) . '/config/font_pairing_catalog.yaml';
        $adapter = new FontManagerFontPreviewAdapter(
            new ProviderRegistry(),
            $this->createMock(FontPairingImportPort::class),
            new FonttrioPairingCatalog($catalogPath),
            pairingsConfig: [
                'catalog' => [
                    'editorial' => [
                        'label' => 'Lab editorial override',
                        'source' => '@fonttrio/editorial',
                    ],
                    'custom-lab' => [
                        'label' => 'Custom lab pairing',
                        'source' => '@fonttrio/swiss',
                    ],
                ],
            ],
            fontsConfig: [],
        );

        $pairings = $adapter->listPairings();
        $indexed = [];
        foreach ($pairings as $entry) {
            $indexed[$entry['id']] = $entry;
        }

        self::assertSame('Lab editorial override', $indexed['editorial']['label']);
        self::assertSame('Custom lab pairing', $indexed['custom-lab']['label']);
        self::assertGreaterThanOrEqual(21, count($pairings));
    }
}
