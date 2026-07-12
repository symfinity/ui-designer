<?php

declare(strict_types=1);

namespace Symfinity\UiDesigner\Tests\Functional\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/host')]
final class HostPreviewFixtureController extends AbstractController
{
    #[Route('', name: 'test_host_home', methods: ['GET'])]
    public function home(): Response
    {
        return $this->render('@UiDesigner/tests/host_home.html.twig');
    }

    #[Route('/kernel', name: 'test_host_kernel', methods: ['GET'])]
    public function kernel(): Response
    {
        return $this->render('@UiDesigner/tests/host_kernel.html.twig');
    }

    #[Route('/fonts', name: 'test_host_fonts', methods: ['GET'])]
    public function fonts(): Response
    {
        return $this->render('@UiDesigner/tests/host_fonts.html.twig');
    }
}
