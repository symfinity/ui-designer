<?php

declare(strict_types=1);

namespace Symfinity\UiDesigner\Tests\Unit\Infrastructure;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfinity\UiDesigner\Infrastructure\UxIconsAvailabilityProbe;

final class UxIconsAvailabilityProbeTest extends TestCase
{
    #[Test]
    public function iconSetsEmptyWhenPackageMissing(): void
    {
        $probe = new UxIconsAvailabilityProbe();

        if ($probe->isInstalled()) {
            self::assertNotEmpty($probe->iconSets());
        } else {
            self::assertSame([], $probe->iconSets());
        }
    }
}
