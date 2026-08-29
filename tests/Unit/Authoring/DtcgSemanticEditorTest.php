<?php

declare(strict_types=1);

namespace Symfinity\UiDesigner\Tests\Unit\Authoring;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfinity\UiDesigner\Authoring\DtcgRolePathMapper;
use Symfinity\UiDesigner\Authoring\DtcgSemanticEditor;
use Symfinity\UiDesigner\Model\DesignerDraft;

final class DtcgSemanticEditorTest extends TestCase
{
    private DtcgSemanticEditor $editor;

    protected function setUp(): void
    {
        $this->editor = new DtcgSemanticEditor(new DtcgRolePathMapper());
    }

    #[Test]
    public function assignAndReadPrimaryRef(): void
    {
        $draft = new DesignerDraft(
            id: 'semantic',
            lineage: 'semantic',
            variantPayloads: [
                'light' => [
                    'color' => [
                        'primary' => ['$type' => 'color', '$value' => '{color.blue.500}'],
                    ],
                ],
            ],
        );

        $updated = $this->editor->assignRoleRef($draft, 'primary', 'red.500');
        self::assertSame('red.500', $this->editor->readRoleRef($updated, 'primary'));
        self::assertSame('{color.red.500}', $updated->variantPayloads['light']['color']['primary']['$value']);
    }

    #[Test]
    public function rejectsHexLiteral(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->editor->assertPaletteRefOnly('primary', '#ff0000');
    }
}
