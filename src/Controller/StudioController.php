<?php

declare(strict_types=1);

namespace Symfinity\UiDesigner\Controller;

use Symfinity\UiDesigner\Accessibility\ContrastPanelPresenter;
use Symfinity\UiDesigner\Accessibility\StrictExportGate;
use Symfinity\UiDesigner\Authoring\DtcgRolePathMapper;
use Symfinity\UiDesigner\Authoring\DtcgSemanticEditor;
use Symfinity\UiDesigner\Authoring\NearestRefMapper;
use Symfinity\UiDesigner\Authoring\SelectedRole;
use Symfinity\UiDesigner\Authoring\StructuralDraft;
use Symfinity\UiDesigner\Collaboration\DraftSnapshot;
use Symfinity\UiDesigner\Collaboration\DraftSnapshotStore;
use Symfinity\UiDesigner\Collaboration\UndoOperation;
use Symfinity\UiDesigner\Collaboration\UndoStack;
use Symfinity\UiDesigner\Export\DtcgExportService;
use Symfinity\UiDesigner\Export\ReviewZipBuilder;
use Symfinity\UiDesigner\Factory\DtcgDraftFactory;
use Symfinity\UiDesigner\Import\ImportRequest;
use Symfinity\UiDesigner\Import\ShadcnRegistryImporter;
use Symfinity\UiDesigner\Kernel\BuiltinDraftLoader;
use Symfinity\UiDesigner\Model\DesignerDraft;
use Symfinity\UiDesigner\Model\DesignerPairSession;
use Symfinity\UiDesigner\Session\DesignerDraftSession;
use Symfinity\UiDesigner\Session\PreviewHostSession;
use Symfinity\UiDesigner\Session\StudioDrawerSession;
use Symfinity\UiKernel\Palette\OklchColorSpace;
use Symfinity\UiKernel\Token\ThemeConfig;
use Symfinity\UiDesigner\Studio\StudioPreviewAugmenter;
use Symfinity\UiDesigner\Studio\TokenGroupRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/ui-designer', env: 'dev')]
final class StudioController extends AbstractController
{
    public function __construct(
        private readonly DesignerDraftSession $draftSession,
        private readonly BuiltinDraftLoader $builtinLoader,
        private readonly StudioPreviewAugmenter $previewAugmenter,
        private readonly ContrastPanelPresenter $contrastPanel,
        private readonly TokenGroupRegistry $tokenGroupRegistry,
        private readonly DtcgSemanticEditor $semanticEditor,
        private readonly DtcgRolePathMapper $pathMapper,
        private readonly UndoStack $undoStack,
        private readonly DraftSnapshotStore $snapshotStore,
        private readonly DtcgDraftFactory $draftFactory,
        private readonly DtcgExportService $exportService,
        private readonly ReviewZipBuilder $reviewZipBuilder,
        private readonly StrictExportGate $strictExportGate,
        private readonly ShadcnRegistryImporter $registryImporter,
        private readonly StudioDrawerSession $drawerSession,
        private readonly PreviewHostSession $previewHostSession,
        private readonly NearestRefMapper $nearestRefMapper,
        private readonly OklchColorSpace $colorSpace = new OklchColorSpace(),
    ) {
    }

    #[Route('', name: 'ui_designer_home', methods: ['GET'], priority: 20)]
    public function homeRedirect(): Response
    {
        return $this->redirectToRoute('ui_designer_import_hub');
    }

    #[Route('/studio', name: 'ui_designer_studio', methods: ['GET'])]
    public function studio(): Response
    {
        $draft = $this->draftSession->current();
        if ($draft === null) {
            return $this->redirectToRoute('ui_designer_import_hub');
        }
        if ($this->drawerSession->isOpen()) {
            $drawer = $this->drawerSession->current();
            $returnUrl = $drawer['return_url'] ?? '/';

            return $this->redirect($returnUrl);
        }
        $structural = $this->draftSession->structural();
        $pair = $this->draftSession->pair();

        return $this->render('@UiDesigner/studio/index.html.twig', [
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
            'semantic_roles' => $this->tokenGroupRegistry->allRoles(),
            'token_groups' => $this->tokenGroupRegistry->groups($draft),
            'role_css_vars' => $this->tokenGroupRegistry->roleCssVars(),
            'selected_role' => $this->draftSession->selectedRole()->activeRole,
            'radius_steps' => StructuralDraft::RADIUS_STEPS,
            'shadow_steps' => StructuralDraft::SHADOW_STEPS,
            'can_undo' => $this->undoStack->canUndo(),
            'active_nav' => 'studio',
        ]);
    }

    #[Route('/studio/load', name: 'ui_designer_studio_load', methods: ['POST'])]
    public function loadPreset(Request $request): Response
    {
        $themeId = trim($request->request->getString('theme_id'));
        if ($themeId === '') {
            $this->addFlash('error', 'theme_id required.');

            return $this->redirectToRoute('ui_designer_import_hub');
        }

        try {
            $this->builtinLoader->loadVariantForStudio($themeId);
        } catch (\InvalidArgumentException $e) {
            $this->addFlash('error', $e->getMessage());

            return $this->redirectToRoute('ui_designer_import_hub');
        }

        $this->undoStack->clear();
        $this->addFlash('success', sprintf('Loaded %s — click Start customizing to preview on your app.', $themeId));

        return $this->redirectToRoute('ui_designer_import_hub');
    }

    #[Route('/studio/api/select-role', name: 'ui_designer_studio_select_role', methods: ['POST'])]
    public function selectRole(Request $request): Response
    {
        $role = trim($request->request->getString('role'));
        if ($role === '' || !$this->pathMapper->isKnownRole($role)) {
            return $this->json(['error' => 'Unknown role'], Response::HTTP_BAD_REQUEST);
        }

        $this->draftSession->storeSelectedRole(new SelectedRole($role, time()));

        return $this->json(['ok' => true, 'role' => $role]);
    }

    #[Route('/studio/api/map-colour', name: 'ui_designer_studio_map_colour', methods: ['POST'])]
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

    #[Route('/studio/api/apply-colour', name: 'ui_designer_studio_apply_colour', methods: ['POST'])]
    public function applyColour(Request $request): Response
    {
        $role = trim($request->request->getString('role'));
        $ref = trim($request->request->getString('ref'));

        if ($role === '' || $ref === '') {
            return $this->json(['error' => 'role and ref required'], Response::HTTP_BAD_REQUEST);
        }

        $draft = $this->draftSession->current();
        if ($draft === null) {
            return $this->json(['error' => 'No draft in session'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $this->semanticEditor->assertPaletteRefOnly($role, $ref);
            $previousRef = $this->semanticEditor->readRoleRef($draft, $role) ?? '';
            $updated = $this->semanticEditor->assignRoleRef($draft, $role, $ref);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        $this->undoStack->push(new UndoOperation(
            role: $role,
            mode: 'single',
            previousRef: $previousRef,
            nextRef: $ref,
            timestamp: time(),
        ));

        $this->draftSession->store($updated, dirty: true);
        $this->draftSession->storeSelectedRole(new SelectedRole($role, time()));
        $structural = $this->draftSession->structural();

        return $this->json([
            'ok' => true,
            'preview_css' => $this->previewAugmenter->previewCss($updated, $structural),
            'semantics' => $this->semanticEditor->allRoleRefs($updated),
            'resolved_colors' => $this->previewAugmenter->resolvedRoleColors($updated),
            'contrast' => $this->contrastPanel->present($updated),
            'can_undo' => $this->undoStack->canUndo(),
        ]);
    }

    #[Route('/studio/api/structural', name: 'ui_designer_studio_structural', methods: ['POST'])]
    public function updateStructural(Request $request): Response
    {
        $draft = $this->draftSession->current();
        if ($draft === null) {
            return $this->json(['error' => 'No draft in session'], Response::HTTP_BAD_REQUEST);
        }

        $current = $this->draftSession->structural();
        $radius = $request->request->has('radius_step')
            ? ($request->request->getString('radius_step') ?: null)
            : $current->radiusStep;
        $shadow = $request->request->has('shadow_step')
            ? ($request->request->getString('shadow_step') ?: null)
            : $current->shadowStep;

        $structural = new StructuralDraft($radius, $shadow);

        try {
            $structural->assertValid();
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        $this->draftSession->storeStructural($structural);

        return $this->json([
            'ok' => true,
            'structural' => $structural->toPayload(),
            'preview_css' => $this->previewAugmenter->previewCss($draft, $structural),
            'resolved_colors' => $this->previewAugmenter->resolvedRoleColors($draft),
        ]);
    }

    #[Route('/studio/api/undo', name: 'ui_designer_studio_undo', methods: ['POST'])]
    public function undo(): Response
    {
        $draft = $this->draftSession->current();
        if ($draft === null) {
            return $this->json(['error' => 'No draft in session'], Response::HTTP_BAD_REQUEST);
        }

        $operation = $this->undoStack->pop();
        if ($operation === null) {
            return $this->json(['error' => 'Nothing to undo'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $updated = $operation->previousRef !== ''
                ? $this->semanticEditor->assignRoleRef($draft, $operation->role, $operation->previousRef)
                : $draft;
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        $this->draftSession->store($updated, dirty: true);
        $structural = $this->draftSession->structural();

        return $this->json([
            'ok' => true,
            'preview_css' => $this->previewAugmenter->previewCss($updated, $structural),
            'semantics' => $this->semanticEditor->allRoleRefs($updated),
            'can_undo' => $this->undoStack->canUndo(),
        ]);
    }

    #[Route('/studio/api/preview-mode', name: 'ui_designer_studio_preview_mode', methods: ['POST'])]
    public function previewMode(Request $request): Response
    {
        $mode = $request->request->getString('mode');
        if (!in_array($mode, [DesignerPairSession::MODE_LIGHT, DesignerPairSession::MODE_DARK], true)) {
            return $this->json(['error' => 'mode must be light or dark'], Response::HTTP_BAD_REQUEST);
        }

        if ($this->draftSession->pair() === null) {
            return $this->json(['error' => 'No light/dark pair in session'], Response::HTTP_BAD_REQUEST);
        }

        $this->draftSession->setActiveMode($mode);
        $draft = $this->draftSession->current();
        $structural = $this->draftSession->structural();

        $binding = $this->previewHostSession->binding();
        if ($binding !== null) {
            $this->previewHostSession->store($binding['token'], $mode);
        }

        return $this->json([
            'ok' => true,
            'mode' => $mode,
            'theme_id' => $draft?->id,
            'preview_css' => $this->previewAugmenter->previewCss($draft, $structural),
            'resolved_colors' => $this->previewAugmenter->resolvedRoleColors($draft),
        ]);
    }

    #[Route('/studio/api/snapshot/save', name: 'ui_designer_studio_snapshot_save', methods: ['POST'])]
    public function saveSnapshot(Request $request): Response
    {
        $draft = $this->draftSession->current();
        if ($draft === null) {
            return $this->json(['error' => 'No draft in session'], Response::HTTP_BAD_REQUEST);
        }

        $label = trim($request->request->getString('label')) ?: 'Snapshot';
        $snapshot = new DraftSnapshot(
            id: bin2hex(random_bytes(4)),
            label: $label,
            draftId: $draft->id,
            createdAt: new \DateTimeImmutable(),
            payload: $draft->toPayload(),
        );

        $path = $this->snapshotStore->save($this->draftFactory->exportRoot(), $snapshot);

        return $this->json(['ok' => true, 'snapshot_id' => $snapshot->id, 'path' => $path]);
    }

    #[Route('/studio/api/snapshots', name: 'ui_designer_studio_snapshots', methods: ['GET'])]
    public function listSnapshots(): Response
    {
        $draft = $this->draftSession->current();
        if ($draft === null) {
            return $this->json(['snapshots' => []]);
        }

        $snapshots = $this->snapshotStore->list($this->draftFactory->exportRoot(), $draft->id);

        return $this->json([
            'snapshots' => array_map(
                static fn (DraftSnapshot $s): array => [
                    'id' => $s->id,
                    'label' => $s->label,
                    'created_at' => $s->createdAt->format(\DateTimeInterface::ATOM),
                ],
                $snapshots,
            ),
        ]);
    }

    #[Route('/studio/api/snapshot/restore', name: 'ui_designer_studio_snapshot_restore', methods: ['POST'])]
    public function restoreSnapshot(Request $request): Response
    {
        $draft = $this->draftSession->current();
        if ($draft === null) {
            return $this->json(['error' => 'No draft in session'], Response::HTTP_BAD_REQUEST);
        }

        $snapshotId = trim($request->request->getString('snapshot_id'));
        if ($snapshotId === '') {
            return $this->json(['error' => 'snapshot_id required'], Response::HTTP_BAD_REQUEST);
        }

        $snapshot = $this->snapshotStore->find($this->draftFactory->exportRoot(), $draft->id, $snapshotId);
        if ($snapshot === null) {
            return $this->json(['error' => 'Snapshot not found'], Response::HTTP_NOT_FOUND);
        }

        $restored = DesignerDraft::fromPayload($snapshot->payload);
        $this->draftSession->store($restored, dirty: true);

        return $this->json([
            'ok' => true,
            'preview_css' => $this->previewAugmenter->previewCss($restored),
            'semantics' => $this->semanticEditor->allRoleRefs($restored),
        ]);
    }

    #[Route('/studio/api/export', name: 'ui_designer_studio_export', methods: ['POST'])]
    public function exportDraft(Request $request): Response
    {
        $draft = $this->draftSession->current();
        if ($draft === null) {
            return $this->json(['error' => 'No draft in session'], Response::HTTP_BAD_REQUEST);
        }

        $skipA11y = $request->request->getBoolean('skip_accessibility');

        try {
            $this->strictExportGate->assertExportAllowed($draft, $skipA11y);
            $path = $this->exportService->export($draft);
            $zipPath = rtrim($this->draftFactory->exportRoot(), '/') . '/review-' . $draft->id . '.zip';
            $this->reviewZipBuilder->build($draft, $zipPath);
        } catch (\Throwable $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return $this->json(['ok' => true, 'export_path' => $path, 'review_zip' => $zipPath]);
    }

    #[Route('/import/registry', name: 'ui_designer_import_registry_review', methods: ['POST'])]
    public function importRegistryReview(Request $request): Response
    {
        $name = trim($request->request->getString('name'));
        if ($name === '') {
            $this->addFlash('error', 'Registry theme name required.');

            return $this->redirectToRoute('ui_designer_import_hub');
        }

        try {
            $result = $this->registryImporter->import(new ImportRequest(type: 'shadcn-theme', name: $name));
        } catch (\Throwable $e) {
            $this->addFlash('error', $e->getMessage());

            return $this->redirectToRoute('ui_designer_import_hub');
        }

        $this->draftSession->clearPair();
        $this->draftSession->store($result->draft, dirty: true);
        $this->draftSession->storeStructural(StructuralDraft::empty());
        $this->addFlash('success', sprintf('Imported registry theme "%s" — click Start customizing to preview on your app.', $result->draft->id));

        return $this->redirectToRoute('ui_designer_import_hub');
    }
}
