<?php

declare(strict_types=1);

namespace Symfinity\UiDesigner\Tests\Functional\Controller;

use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfinity\UiDesigner\Tests\Integration\UiDesignerTestKernel;

final class PreviewLiveSyncTest extends WebTestCase
{
    protected static function getKernelClass(): string
    {
        return UiDesignerTestKernel::class;
    }

    protected static function createKernel(array $options = []): UiDesignerTestKernel
    {
        return new UiDesignerTestKernel('dev', true);
    }

    #[Test]
    public function applyColourUpdatesHostCss(): void
    {
        $client = static::createClient();
        $client->disableReboot();
        $client->request('POST', '/ui-designer/studio/load', ['theme_id' => 'semantic']);
        $client->followRedirect();
        $client->request('POST', '/ui-designer/drawer/start', ['return_url' => '/host']);
        $client->followRedirect();

        parse_str(parse_url($client->getRequest()->getUri(), PHP_URL_QUERY) ?: '', $query);
        self::assertNotEmpty($query['preview'] ?? '');
        $client->request('GET', '/host', $query);
        $before = $client->getResponse()->getContent() ?: '';
        self::assertMatchesRegularExpression('/<style id="ui-designer-preview-css">/s', $before);

        if (!preg_match('/id="ui-designer-preview-css">(.*?)<\/style>/s', $before, $beforeMatch)) {
            self::fail('Missing preview CSS block on host.');
        }
        $cssBefore = trim($beforeMatch[1]);

        $client->request(
            'POST',
            '/ui-designer/drawer/api/apply-colour',
            ['role' => 'primary', 'ref' => 'red.500', 'confirm' => '0'],
            server: ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'],
        );
        self::assertResponseIsSuccessful();
        $payload = json_decode($client->getResponse()->getContent() ?: '', true, 512, JSON_THROW_ON_ERROR);
        self::assertTrue($payload['ok']);
        self::assertNotSame('', trim($payload['preview_css'] ?? ''));
        self::assertNotSame($cssBefore, trim($payload['preview_css']));
    }
}
