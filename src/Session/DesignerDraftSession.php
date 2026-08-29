<?php

declare(strict_types=1);

namespace Symfinity\UiDesigner\Session;

use Symfinity\UiDesigner\Authoring\SelectedRole;
use Symfinity\UiDesigner\Authoring\StructuralDraft;
use Symfinity\UiDesigner\Model\AuthorGraphNode;
use Symfinity\UiDesigner\Model\DesignerDraft;
use Symfinity\UiDesigner\Model\DesignerPairSession;
use Symfinity\UiDesigner\Util\PayloadUtil;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

final class DesignerDraftSession
{
    private const DRAFT_KEY = 'ui_designer.draft';
    private const DIRTY_KEY = 'ui_designer.draft_dirty';
    private const PAIR_KEY = 'ui_designer.pair';
    private const STRUCTURAL_KEY = 'ui_designer.structural';
    private const SELECTED_ROLE_KEY = 'ui_designer.selected_role';

    private ?DesignerDraft $fallbackDraft = null;

    private bool $fallbackDirty = false;

    private ?DesignerPairSession $fallbackPair = null;

    private ?StructuralDraft $fallbackStructural = null;

    private ?SelectedRole $fallbackSelectedRole = null;

    public function __construct(
        private readonly RequestStack $requestStack,
    ) {
    }

    public function current(): ?DesignerDraft
    {
        $pair = $this->pair();
        if ($pair !== null) {
            return $pair->activeDraft();
        }

        $session = $this->session();
        if ($session === null) {
            return $this->fallbackDraft;
        }

        $payload = $session->get(self::DRAFT_KEY);
        if (!is_array($payload)) {
            return null;
        }

        return DesignerDraft::fromPayload(PayloadUtil::stringKeyMap($payload));
    }

    public function isDirty(): bool
    {
        $session = $this->session();
        if ($session === null) {
            return $this->fallbackDirty;
        }

        return (bool) $session->get(self::DIRTY_KEY, false);
    }

    public function store(DesignerDraft $draft, bool $dirty = false): void
    {
        $pair = $this->pair();
        if ($pair !== null) {
            $this->storePair($pair->withActiveDraft($draft), $dirty);

            return;
        }

        $session = $this->session();
        if ($session === null) {
            $this->fallbackDraft = $draft;
            $this->fallbackDirty = $dirty;

            return;
        }

        $session->set(self::DRAFT_KEY, $draft->toPayload());
        $session->set(self::DIRTY_KEY, $dirty);
    }

    public function pair(): ?DesignerPairSession
    {
        $session = $this->session();
        if ($session === null) {
            return $this->fallbackPair;
        }

        $payload = $session->get(self::PAIR_KEY);
        if (!is_array($payload)) {
            return null;
        }

        return DesignerPairSession::fromPayload(PayloadUtil::stringKeyMap($payload));
    }

    public function storePair(DesignerPairSession $pair, bool $dirty = false): void
    {
        $session = $this->session();
        if ($session === null) {
            $this->fallbackPair = $pair;
            $this->fallbackDraft = $pair->activeDraft();
            $this->fallbackDirty = $dirty;

            return;
        }

        $session->set(self::PAIR_KEY, $pair->toPayload());
        $session->remove(self::DRAFT_KEY);
        $session->set(self::DIRTY_KEY, $dirty);
    }

    public function clearPair(): void
    {
        $session = $this->session();
        if ($session === null) {
            $this->fallbackPair = null;

            return;
        }

        $session->remove(self::PAIR_KEY);
    }

    public function setActiveMode(string $mode): void
    {
        $pair = $this->pair();
        if ($pair === null) {
            return;
        }

        $this->storePair($pair->withActiveMode($mode), $this->isDirty());
    }

    public function structural(): StructuralDraft
    {
        $session = $this->session();
        if ($session === null) {
            return $this->fallbackStructural ?? StructuralDraft::empty();
        }

        $payload = $session->get(self::STRUCTURAL_KEY);
        if (!is_array($payload)) {
            return StructuralDraft::empty();
        }

        return StructuralDraft::fromPayload(PayloadUtil::stringKeyMap($payload));
    }

    public function storeStructural(StructuralDraft $structural): void
    {
        $session = $this->session();
        if ($session === null) {
            $this->fallbackStructural = $structural;

            return;
        }

        $session->set(self::STRUCTURAL_KEY, $structural->toPayload());
    }

    public function selectedRole(): SelectedRole
    {
        $session = $this->session();
        if ($session === null) {
            return $this->fallbackSelectedRole ?? new SelectedRole('primary', time());
        }

        $payload = $session->get(self::SELECTED_ROLE_KEY);
        if (!is_array($payload)) {
            return new SelectedRole('primary', time());
        }

        return SelectedRole::fromPayload(PayloadUtil::stringKeyMap($payload));
    }

    public function storeSelectedRole(SelectedRole $selection): void
    {
        $session = $this->session();
        if ($session === null) {
            $this->fallbackSelectedRole = $selection;

            return;
        }

        $session->set(self::SELECTED_ROLE_KEY, $selection->toPayload());
    }

    public function reorderGraphNode(string $nodeId, int $offset): ?DesignerDraft
    {
        $draft = $this->current();
        if ($draft === null) {
            return null;
        }

        $graph = $draft->graph;
        $index = null;
        foreach ($graph as $i => $node) {
            if ($node->nodeId === $nodeId) {
                $index = $i;
                break;
            }
        }

        if ($index === null) {
            return $draft;
        }

        $target = $index + $offset;
        if ($target < 0 || $target >= count($graph)) {
            return $draft;
        }

        $node = $graph[$index];
        array_splice($graph, $index, 1);
        array_splice($graph, $target, 0, [$node]);

        $updated = new DesignerDraft(
            id: $draft->id,
            lineage: $draft->lineage,
            designSystemId: $draft->designSystemId,
            graph: $graph,
            variants: $draft->variants,
            variantPayloads: $draft->variantPayloads,
        );
        $this->store($updated, $this->isDirty());

        return $updated;
    }

    public function moveGraphNodeUp(string $nodeId): ?DesignerDraft
    {
        return $this->reorderGraphNode($nodeId, -1);
    }

    public function moveGraphNodeDown(string $nodeId): ?DesignerDraft
    {
        return $this->reorderGraphNode($nodeId, 1);
    }

    public function clear(): void
    {
        $session = $this->session();
        if ($session === null) {
            $this->fallbackDraft = null;
            $this->fallbackDirty = false;
            $this->fallbackPair = null;
            $this->fallbackStructural = null;
            $this->fallbackSelectedRole = null;

            return;
        }

        $session->remove(self::DRAFT_KEY);
        $session->remove(self::DIRTY_KEY);
        $session->remove(self::PAIR_KEY);
        $session->remove(self::STRUCTURAL_KEY);
        $session->remove(self::SELECTED_ROLE_KEY);
    }

    private function session(): ?SessionInterface
    {
        $request = $this->requestStack->getCurrentRequest();
        if ($request === null || !$request->hasSession()) {
            return null;
        }

        return $request->getSession();
    }
}
