<?php

declare(strict_types=1);

namespace Symfinity\UiDesigner\Collaboration;

use Symfinity\UiDesigner\Util\PayloadUtil;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

final class UndoStack
{
    private const SESSION_KEY = 'ui_designer.undo_stack';

    /** @var list<UndoOperation> */
    private array $fallback = [];

    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly int $maxDepth = 20,
    ) {
    }

    public function push(UndoOperation $operation): void
    {
        $stack = $this->operations();
        $stack[] = $operation;
        if (count($stack) > $this->maxDepth) {
            $stack = array_slice($stack, -$this->maxDepth);
        }
        $this->store($stack);
    }

    public function pop(): ?UndoOperation
    {
        $stack = $this->operations();
        if ($stack === []) {
            return null;
        }

        $operation = array_pop($stack);
        $this->store($stack);

        return $operation;
    }

    public function canUndo(): bool
    {
        return $this->operations() !== [];
    }

    public function clear(): void
    {
        $this->store([]);
    }

    /**
     * @return list<UndoOperation>
     */
    private function operations(): array
    {
        $session = $this->session();
        if ($session === null) {
            return $this->fallback;
        }

        $payload = $session->get(self::SESSION_KEY);
        if (!is_array($payload)) {
            return [];
        }

        $operations = [];
        foreach ($payload as $item) {
            if (!is_array($item)) {
                continue;
            }
            $operations[] = UndoOperation::fromPayload(PayloadUtil::stringKeyMap($item));
        }

        return $operations;
    }

    /**
     * @param list<UndoOperation> $operations
     */
    private function store(array $operations): void
    {
        $payload = array_map(static fn (UndoOperation $op): array => $op->toPayload(), $operations);
        $session = $this->session();
        if ($session === null) {
            $this->fallback = $operations;

            return;
        }

        $session->set(self::SESSION_KEY, $payload);
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
