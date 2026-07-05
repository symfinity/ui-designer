<?php

declare(strict_types=1);

namespace Symfinity\UiDesigner\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/ui-designer', env: 'dev')]
final class ImportHubController extends AbstractController
{
    #[Route('/import', name: 'ui_designer_import_hub', methods: ['GET'])]
    public function hub(): Response
    {
        return $this->render('@UiDesigner/import/hub.html.twig', [
            'active_nav' => 'import',
        ]);
    }
}
