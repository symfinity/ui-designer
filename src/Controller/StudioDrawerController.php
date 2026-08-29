<?php

declare(strict_types=1);

namespace Symfinity\UiDesigner\Controller;

use Symfinity\UiDesigner\Accessibility\ContrastPanelPresenter;
use Symfinity\UiDesigner\Authoring\NearestRefMapper;
use Symfinity\UiDesigner\Authoring\StructuralDraft;
use Symfinity\UiDesigner\Infrastructure\UxIconsAvailabilityProbe;
use Symfinity\UiDesigner\Collaboration\UndoStack;
use Symfinity\UiDesigner\Kernel\BuiltinDraftLoader;
use Symfinity\UiDesigner\Model\DesignerPairSession;
use Symfinity\UiDesigner\Preview\DesignerFullPaletteResolver;
use Symfinity\UiDesigner\Preview\PreviewSession;
use Symfinity\UiDesigner\Preview\PreviewSessionStore;
use Symfinity\UiDesigner\Session\DesignerDraftSession;
use Symfinity\UiDesigner\Session\PreviewHostSession;
use Symfinity\UiDesigner\Session\StudioDrawerSession;
use Symfinity\UiDesigner\Session\TypographyPreviewSession;
use Symfinity\UiDesigner\Studio\StudioPreviewAugmenter;
use Symfinity\UiDesigner\Studio\TokenGroupRegistry;
use Symfinity\UiDesigner\Typography\FontPreviewPort;
use Symfinity\UiDesigner\Typography\TypographySelection;
use Symfinity\UiKernel\Palette\OklchColorSpace;
use Symfinity\UiKernel\Token\ThemeConfig;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\RouterInterface;

#[Route('/ui-designer', env: 'dev')]
final class StudioDrawerController extends AbstractController
{
    public function __construct(
        private readonly DesignerDraftSession $draftSession,
        private readonly StudioDrawerSession $drawerSession,
        private readonly PreviewHostSession $previewHostSession,
        private readonly PreviewSessionStore $previewSessionStore,
        private readonly BuiltinDraftLoader $builtinLoader,
        private readonly StudioPreviewAugmenter $previewAugmenter,
        private readonly ContrastPanelPresenter $contrastPanel,
        private readonly TokenGroupRegistry $tokenGroupRegistry,
        private readonly UndoStack $undoStack,
        private readonly NearestRefMapper $nearestRefMapper,
        private readonly RouterInterface $router,
        private readonly FontPreviewPort $fontPreview,
        private readonly TypographyPreviewSession $typographyPreviewSession,
        private readonly DesignerFullPaletteResolver $fullPaletteResolver,
        private readonly UxIconsAvailabilityProbe $uxIconsProbe,
        private readonly RequestStack $requestStack,
        private readonly OklchColorSpace $colorSpace = new OklchColorSpace(),
    ) {
    }

    #[Route('/drawer/start', name: 'ui_designer_drawer_start', methods: ['POST'])]
    public function startCustomizing(Request $request): Response
    {
        $draft = $this->draftSession->current();
        if ($draft === null) {
            $this->addFlash('error', 'Load a preset before customizing.');

            return $this->redirectToRoute('ui_designer_import_hub');
        }

        $returnUrl = trim($request->request->getString('return_url')) ?: '/';
        if (!str_starts_with($returnUrl, '/')) {
            $returnUrl = '/';
        }

        $pair = $this->draftSession->pair();
        $polarity = $pair !== null && $pair->activeMode === DesignerPairSession::MODE_DARK
            ? PreviewSession::POLARITY_DARK
            : PreviewSession::POLARITY_LIGHT;

        $session = $pair !== null
            ? $this->previewSessionStore->mintFromPair($pair)
            : $this->previewSessionStore->mint($draft, activePolarity: $polarity);

        $this->previewHostSession->store($session->token, $polarity);
        $this->drawerSession->open($returnUrl, $session->token);
        $this->seedTypographyPreview();

        return $this->redirectToHost($returnUrl, $session->token);
    }

    #[Route('/drawer/close', name: 'ui_designer_drawer_close', methods: ['POST'])]
    public function closeDrawer(Request $request): Response
    {
        $drawer = $this->drawerSession->current();
        $this->drawerSession->close();

        return $this->redirectToHost(
            $this->resolveHostReturnUrl($request, $drawer),
            is_array($drawer) ? ($drawer['preview_token'] ?? null) : null,
        );
    }

    #[Route('/drawer/reopen', name: 'ui_designer_drawer_reopen', methods: ['POST'])]
    public function reopenDrawer(Request $request): Response
    {
        $draft = $this->draftSession->current();
        if ($draft === null) {
            $this->addFlash('error', 'Load a preset before customizing.');

            return $this->redirectToRoute('ui_designer_import_hub');
        }

        $drawer = $this->drawerSession->current();
        if ($drawer === null) {
            return $this->startCustomizing($request);
        }

        $returnUrl = $this->resolveHostReturnUrl($request, $drawer);

        $previewToken = $drawer['preview_token'];
        if ($previewToken === null) {
            return $this->startCustomizing($request);
        }

        $this->drawerSession->open($returnUrl, $previewToken, $drawer['active_tab']);

        return $this->redirectToHost($returnUrl, $previewToken);
    }

    #[Route('/drawer/fragment', name: 'ui_designer_drawer_fragment', methods: ['GET'])]
    public function drawerFragment(Request $request): Response
    {
        if (!$this->drawerSession->isOpen()) {
            return new Response('', Response::HTTP_NO_CONTENT);
        }

        return $this->renderDrawerShell($request);
    }

    #[Route('/drawer/api/map-colour', name: 'ui_designer_drawer_map_colour', methods: ['POST'])]
    public function mapColour(Request $request): Response
    {
        $literal = trim($request->request->getString('literal'));
        $role = trim($request->request->getString('role'));
        if ($literal === '' || $role === '') {
            return $this->json(['error' => 'literal and role required'], Response::HTTP_BAD_REQUEST);
        }

        $draft = $this->draftSession->current();
        if ($draft === null) {
            return $this->json(['error' => 'No draft in session'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $tuple = str_starts_with($literal, '#')
                ? $this->colorSpace->fromHex($literal)
                : $this->colorSpace->parse($literal);
        } catch (\Throwable $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        $recipe = ThemeConfig::get($draft->id)->paletteRecipe();
        $mapped = $this->nearestRefMapper->map($tuple, null, $recipe);

        return $this->json([
            'ref' => $mapped['ref'],
            'confidence' => $mapped['confidence'],
            'deltaE' => $mapped['deltaE'],
            'status' => $mapped['status'],
            'requiresConfirm' => $mapped['status'] === NearestRefMapper::STATUS_LOW_CONFIDENCE,
        ]);
    }

    #[Route('/drawer/api/apply-colour', name: 'ui_designer_drawer_apply_colour', methods: ['POST'])]
    public function applyColour(Request $request): Response
    {
        return $this->forward(StudioController::class . '::applyColour', [
            'request' => $request,
        ]);
    }

    #[Route('/drawer/api/preview-mode', name: 'ui_designer_drawer_preview_mode', methods: ['POST'])]
    public function previewMode(Request $request): Response
    {
        return $this->forward(StudioController::class . '::previewMode', [
            'request' => $request,
        ]);
    }

    #[Route('/drawer/api/undo', name: 'ui_designer_drawer_undo', methods: ['POST'])]
    public function undo(): Response
    {
        return $this->forward(StudioController::class . '::undo');
    }

    #[Route('/drawer/api/select-role', name: 'ui_designer_drawer_select_role', methods: ['POST'])]
    public function selectRole(Request $request): Response
    {
        return $this->forward(StudioController::class . '::selectRole', [
            'request' => $request,
        ]);
    }

    #[Route('/drawer/api/tab', name: 'ui_designer_drawer_tab', methods: ['POST'])]
    public function setActiveTab(Request $request): Response
    {
        $tab = trim($request->request->getString('tab'));
        $allowed = [
            StudioDrawerSession::TAB_COLOURS,
            StudioDrawerSession::TAB_TYPOGRAPHY,
            StudioDrawerSession::TAB_STRUCTURE,
        ];

        if (!in_array($tab, $allowed, true)) {
            return $this->json(['error' => 'Unknown tab'], Response::HTTP_BAD_REQUEST);
        }

        if (!$this->drawerSession->isOpen()) {
            return $this->json(['error' => 'Drawer is not open'], Response::HTTP_BAD_REQUEST);
        }

        $this->drawerSession->setActiveTab($tab);

        return $this->json(['ok' => true, 'tab' => $tab]);
    }

    #[Route('/drawer/api/structural', name: 'ui_designer_drawer_structural', methods: ['POST'])]
    public function updateStructural(Request $request): Response
    {
        return $this->forward(StudioController::class . '::updateStructural', [
            'request' => $request,
        ]);
    }

    #[Route('/drawer/api/fonts/search', name: 'ui_designer_drawer_fonts_search', methods: ['POST'])]
    public function searchFonts(Request $request): Response
    {
        if (!$this->fontPreview->isAvailable()) {
            return $this->json(['error' => 'Font preview requires symfinity/font-manager'], Response::HTTP_SERVICE_UNAVAILABLE);
        }

        $query = trim($request->request->getString('query'));
        $limit = max(1, min(40, $request->request->getInt('limit', 20)));

        return $this->json([
            'ok' => true,
            'search_available' => $this->fontPreview->searchAvailable(),
            'results' => $this->fontPreview->search($query, $limit),
        ]);
    }

    #[Route('/drawer/api/fonts/role', name: 'ui_designer_drawer_fonts_role', methods: ['POST'])]
    public function setFontRole(Request $request): Response
    {
        if (!$this->fontPreview->isAvailable()) {
            return $this->json(['error' => 'Font preview requires symfinity/font-manager'], Response::HTTP_SERVICE_UNAVAILABLE);
        }

        $role = trim($request->request->getString('role'));
        $family = trim($request->request->getString('family'));
        if (!in_array($role, ['heading', 'body', 'mono'], true) || $family === '') {
            return $this->json(['error' => 'role and family required'], Response::HTTP_BAD_REQUEST);
        }

        $current = $this->typographyPreviewSession->current()
            ?? $this->fontPreview->selectionFromActiveConfig()
            ?? new TypographySelection();

        $selection = $current->withRoleFamily($role, $family);
        $this->typographyPreviewSession->store($selection);

        return $this->jsonTypographySelection($selection);
    }

    #[Route('/drawer/api/fonts/pairing', name: 'ui_designer_drawer_fonts_pairing', methods: ['POST'])]
    public function applyFontPairing(Request $request): Response
    {
        if (!$this->fontPreview->isAvailable()) {
            return $this->json(['error' => 'Font preview requires symfinity/font-manager'], Response::HTTP_SERVICE_UNAVAILABLE);
        }

        $pairingId = trim($request->request->getString('pairing_id'));
        if ($pairingId === '') {
            return $this->json(['error' => 'pairing_id required'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $selection = $this->fontPreview->selectionFromPairing($pairingId);
        } catch (\Throwable $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        $this->typographyPreviewSession->store($selection);

        return $this->jsonTypographySelection($selection);
    }

    private function seedTypographyPreview(): void
    {
        if (!$this->fontPreview->isAvailable()) {
            return;
        }

        if ($this->typographyPreviewSession->current() !== null) {
            return;
        }

        $selection = $this->fontPreview->selectionFromActiveConfig();
        if ($selection !== null) {
            $this->typographyPreviewSession->store($selection);
        }
    }

    private function jsonTypographySelection(TypographySelection $selection): Response
    {
        return $this->json([
            'ok' => true,
            'selection' => $selection->toPayload(),
            'preview_html' => $this->fontPreview->renderPreviewHtml($selection),
        ]);
    }

    private function renderDrawerShell(Request $request): Response
    {
        $draft = $this->draftSession->current();
        $structural = $this->draftSession->structural();
        $pair = $this->draftSession->pair();
        $drawer = $this->drawerSession->current();

        return $this->render('@UiDesigner/drawer/_shell.html.twig', [
            'draft' => $draft,
            'structural' => $structural,
            'presets' => $this->builtinLoader->presetEntries(),
            'active_preset_id' => $draft?->id,
            'preview_css' => $this->previewAugmenter->previewCss($draft, $structural),
            'preview_mode' => $this->previewAugmenter->activeMode(),
            'has_dark_variant' => $pair !== null,
            'resolved_colors' => $this->previewAugmenter->resolvedRoleColors($draft),
            'contrast_panel' => $draft !== null
                ? $this->contrastPanel->present($draft)
                : ['available' => false, 'notice' => 'Load a preset to review contrast.', 'rows' => []],
            'token_groups' => $this->tokenGroupRegistry->groups($draft),
            'token_roles' => $this->tokenGroupRegistry->orderedRoles($draft),
            'role_css_vars' => $this->tokenGroupRegistry->roleCssVars(),
            'selected_role' => $this->draftSession->selectedRole()->activeRole,
            'radius_steps' => StructuralDraft::RADIUS_STEPS,
            'shadow_steps' => StructuralDraft::SHADOW_STEPS,
            'can_undo' => $this->undoStack->canUndo(),
            'active_tab' => $drawer['active_tab'] ?? StudioDrawerSession::TAB_COLOURS,
            'current_path' => $this->hostRequestPath(),
            'host_return_url' => $this->hostRequestPath(),
            'current_route' => $request->attributes->get('_route'),
            'hub_routes' => $this->hubRoutes(),
            'typography' => $this->typographyPanelContext(),
            'full_palette' => $draft !== null ? $this->fullPaletteResolver->resolve($draft) : null,
            'ux_icons' => [
                'installed' => $this->uxIconsProbe->isInstalled(),
                'sets' => $this->uxIconsProbe->iconSets(),
            ],
        ]);
    }

    #[Route('/drawer/api/palette', name: 'ui_designer_drawer_palette', methods: ['GET'])]
    public function paletteData(): Response
    {
        $draft = $this->draftSession->current();
        if ($draft === null) {
            return $this->json(['error' => 'No draft in session'], Response::HTTP_BAD_REQUEST);
        }

        return $this->json([
            'ok' => true,
            'palette' => $this->fullPaletteResolver->resolve($draft),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function typographyPanelContext(): array
    {
        $selection = $this->typographyPreviewSession->current()
            ?? $this->fontPreview->selectionFromActiveConfig();

        return [
            'available' => $this->fontPreview->isAvailable(),
            'search_available' => $this->fontPreview->searchAvailable(),
            'selection' => $selection,
            'pairings' => $this->fontPreview->listPairings(),
            'popular' => $this->fontPreview->popularFamilies(),
            'preview_html' => $selection !== null && !$selection->isEmpty()
                ? $this->fontPreview->renderPreviewHtml($selection)
                : '',
        ];
    }

    /**
     * @return list<array{label: string, route: string}>
     */
    private function hubRoutes(): array
    {
        $routes = [];
        foreach ([
            ['label' => 'Hub', 'route' => 'ui_lab_home'],
            ['label' => 'Kernel', 'route' => 'ui_lab_kernel'],
            ['label' => 'Fonts', 'route' => 'ui_lab_fonts'],
        ] as $entry) {
            if ($this->router->getRouteCollection()->get($entry['route']) !== null) {
                $routes[] = $entry;
            }
        }

        return $routes;
    }

    /**
     * @param array{open: bool, active_tab: string, return_url: string, preview_token: ?string}|null $drawer
     */
    private function resolveHostReturnUrl(Request $request, ?array $drawer): string
    {
        $mainPath = $this->hostRequestPath();
        if ($this->isValidHostReturnUrl($mainPath)) {
            return $mainPath;
        }

        $formPath = trim($request->request->getString('return_url'));
        if ($this->isValidHostReturnUrl($formPath)) {
            return $formPath;
        }

        $sessionPath = is_array($drawer) ? $drawer['return_url'] : '/';
        if ($this->isValidHostReturnUrl($sessionPath)) {
            return $sessionPath;
        }

        return '/';
    }

    private function hostRequestPath(): string
    {
        $main = $this->requestStack->getMainRequest();
        $path = $main?->getPathInfo() ?? $this->requestStack->getCurrentRequest()?->getPathInfo() ?? '/';

        return $path !== '' ? $path : '/';
    }

    private function isValidHostReturnUrl(string $path): bool
    {
        return $path !== ''
            && str_starts_with($path, '/')
            && !str_starts_with($path, '/ui-designer');
    }

    private function redirectToHost(string $returnUrl, ?string $previewToken = null): Response
    {
        if (!$this->isValidHostReturnUrl($returnUrl)) {
            $returnUrl = '/';
        }

        $binding = $this->previewHostSession->binding();
        $token = $previewToken ?? ($binding['token'] ?? '');
        $polarity = $binding['polarity'] ?? '';

        if ($token === '') {
            return $this->redirect($returnUrl);
        }

        $query = http_build_query(array_filter([
            'preview' => $token,
            'polarity' => $polarity,
        ]));

        return $this->redirect($returnUrl . (str_contains($returnUrl, '?') ? '&' : '?') . $query);
    }
}
