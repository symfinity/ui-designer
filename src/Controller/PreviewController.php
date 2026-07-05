<?php

declare(strict_types=1);

namespace Symfinity\UiDesigner\Controller;

use Symfinity\UiDesigner\Preview\DesignerPreviewTokenResolver;
use Symfinity\UiDesigner\Preview\PreviewSession;
use Symfinity\UiDesigner\Preview\PreviewSessionStore;
use Symfinity\UiDesigner\Session\DesignerDraftSession;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/ui-designer', env: 'dev')]
final class PreviewController extends AbstractController
{
    public function __construct(
        private readonly DesignerDraftSession $draftSession,
        private readonly PreviewSessionStore $previewSessionStore,
        private readonly DesignerPreviewTokenResolver $previewTokenResolver,
    ) {
    }

    #[Route('/preview/kernel', name: 'ui_designer_preview_kernel', methods: ['POST'])]
    public function openKernelGallery(Request $request): Response
    {
        $draft = $this->draftSession->current();
        if ($draft === null) {
            $this->addFlash('error', 'Load a preset before opening kernel preview.');

            return $this->redirectToRoute('ui_designer_studio');
        }

        $pair = $this->draftSession->pair();
        $polarity = $request->request->getString('polarity');
        if ($polarity === '') {
            $polarity = $pair !== null && $pair->activeMode === 'dark'
                ? PreviewSession::POLARITY_DARK
                : PreviewSession::POLARITY_LIGHT;
        }

        $session = $pair !== null
            ? $this->previewSessionStore->mintFromPair($pair)
            : $this->previewSessionStore->mint($draft, activePolarity: $polarity);

        $query = http_build_query(array_filter([
            'preview' => $session->token,
            'polarity' => $polarity,
            'carousel' => '0',
        ]));

        return $this->redirect('/kernel?' . $query);
    }

    #[Route('/preview/exit', name: 'ui_designer_preview_exit', methods: ['GET'])]
    public function previewExit(Request $request): Response
    {
        $token = $request->query->getString('preview');
        if ($token !== '') {
            $this->previewTokenResolver->revoke($token);
        }

        return $this->redirectToRoute('ui_designer_studio');
    }
}
