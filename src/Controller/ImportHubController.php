<?php

declare(strict_types=1);

namespace Symfinity\UiDesigner\Controller;

use Symfinity\UiDesigner\Kernel\BuiltinDraftLoader;
use Symfinity\UiDesigner\Session\DesignerDraftSession;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/ui-designer', env: 'dev')]
final class ImportHubController extends AbstractController
{
    public function __construct(
        private readonly DesignerDraftSession $draftSession,
        private readonly BuiltinDraftLoader $builtinLoader,
    ) {
    }

    #[Route('/import', name: 'ui_designer_import_hub', methods: ['GET'])]
    public function hub(): Response
    {
        $draft = $this->draftSession->current();

        return $this->render('@UiDesigner/import/hub.html.twig', [
            'active_nav' => 'import',
            'draft' => $draft,
            'presets' => $this->builtinLoader->presetEntries(),
            'active_preset_id' => $draft?->id,
        ]);
    }
}
